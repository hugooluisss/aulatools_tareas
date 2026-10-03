<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Enrollments\Controller\SubjectBindingController;
use App\Enrollments\Repository\SubjectBindingRepository;
use App\Enrollments\Service\EnrollmentException;
use App\Enrollments\Service\SubjectBindingService;
use App\Enrollments\Service\TaskDeliveryEnrollmentHook;
use App\Shared\TransactionRunner;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class SubjectBindingServiceTest extends TestCase
{
    public function testSingleBindingCreatesDelivery(): void
    {
        $repository = new SubjectBindingRepositoryFake();
        $hook = new SubjectBindingHookFake();
        $service = new SubjectBindingService($repository, new TransactionRunner($this->createMock(ConnectionInterface::class)), $hook);

        self::assertSame(
            ['student_id' => 57, 'subject_id' => 39, 'cycle_id' => 14],
            $service->enrollSubject(new CurrentUser(1, 'admin', 13), 39, 57, 14),
        );
        self::assertSame([[39, 14, 57]], $repository->added);
        self::assertSame([[57, 39, 14]], $hook->calls);
    }

    public function testBulkIsAdminOnly(): void
    {
        $service = new SubjectBindingService(new SubjectBindingRepositoryFake(), new TransactionRunner($this->createMock(ConnectionInterface::class)), new SubjectBindingHookFake());
        try {
            $service->enrollSubjectsBulk(new CurrentUser(2, 'teacher', 13), 39, 14, [57]);
            self::fail('Expected forbidden error.');
        } catch (EnrollmentException $exception) {
            self::assertSame(403, $exception->status);
        }
    }

    public function testReassignTeachers(): void
    {
        $repository = new SubjectBindingRepositoryFake();
        $repository->bindings = [57, 58];
        $transaction = $this->createMock(\Yiisoft\Db\Transaction\TransactionInterface::class);
        $transaction->expects(self::once())->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        $service = new SubjectBindingService($repository, new TransactionRunner($db), new SubjectBindingHookFake());

        self::assertSame(['updated' => 2], $service->reassignTeachers(new CurrentUser(1, 'admin', 13), 39, 14, [57, 58], 61));
        self::assertSame([[39, 14, [57, 58], 61]], $repository->reassigned);
    }

    public function testReassignRejectsStudentWithoutBinding(): void
    {
        $repository = new SubjectBindingRepositoryFake();
        $repository->bindings = [57];
        $service = new SubjectBindingService($repository, new TransactionRunner($this->createMock(ConnectionInterface::class)), new SubjectBindingHookFake());
        try {
            $service->reassignTeachers(new CurrentUser(1, 'admin', 13), 39, 14, [57, 58], 61);
            self::fail('Expected missing binding rejection.');
        } catch (EnrollmentException $exception) {
            self::assertSame(400, $exception->status);
        }
        self::assertSame([], $repository->reassigned);
    }

    public function testReassignControllerRejectsMissingOrNullTeacherId(): void
    {
        $controller = new SubjectBindingController(
            new SubjectBindingService(new SubjectBindingRepositoryFake(), new TransactionRunner($this->createMock(ConnectionInterface::class)), new SubjectBindingHookFake()),
            $factory = $this->createMock(ResponseFactoryInterface::class),
        );
        $response = $this->createMock(ResponseInterface::class);
        $response->method('withHeader')->willReturnSelf();
        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->expects(self::exactly(2))->method('write')->with(self::stringContains('Invalid teacher_id.'));
        $response->method('getBody')->willReturn($responseBody);
        $factory->expects(self::exactly(2))->method('createResponse')->with(400)->willReturn($response);
        foreach ([['cycle_id' => 14, 'student_ids' => [57]], ['cycle_id' => 14, 'student_ids' => [57], 'teacher_id' => null]] as $body) {
            $request = $this->createMock(ServerRequestInterface::class);
            $stream = $this->createMock(StreamInterface::class);
            $stream->method('__toString')->willReturn(json_encode($body));
            $request->method('getBody')->willReturn($stream);
            $controller->reassignTeachers($request, 39);
        }
    }

    public function testReassignIsAdminOnly(): void
    {
        $service = new SubjectBindingService(new SubjectBindingRepositoryFake(), new TransactionRunner($this->createMock(ConnectionInterface::class)), new SubjectBindingHookFake());
        try {
            $service->reassignTeachers(new CurrentUser(2, 'teacher', 13), 39, 14, [57], 61);
            self::fail('Expected forbidden error.');
        } catch (EnrollmentException $exception) {
            self::assertSame(403, $exception->status);
        }
    }
}

final class SubjectBindingRepositoryFake extends SubjectBindingRepository
{
    public array $added = [];
    public array $bindings = [];
    public array $reassigned = [];
    public function __construct() {}
    public function studentBelongsToSchool(int $schoolId, int $studentId): bool { return true; }
    public function subject(int $schoolId, int $subjectId): ?array { return ['id' => $subjectId]; }
    public function cycle(int $schoolId, int $cycleId): ?array { return ['id' => $cycleId, 'status' => 'active']; }
    public function bindStudent(int $subjectId, int $cycleId, int $studentId): bool { $this->added[] = [$subjectId, $cycleId, $studentId]; return true; }
    public function teacherBelongsToSchool(int $schoolId, int $teacherId): bool { return true; }
    public function bindingStudents(int $subjectId, int $cycleId, array $studentIds): array { return array_map(static fn ($id) => ['student_id' => $id], $this->bindings); }
    public function reassignTeachers(int $subjectId, int $cycleId, array $studentIds, int $teacherId): int { $this->reassigned[] = [$subjectId, $cycleId, $studentIds, $teacherId]; return count($studentIds); }
}

final class SubjectBindingHookFake implements TaskDeliveryEnrollmentHook
{
    public array $calls = [];
    public function onStudentEnrolled(int $studentId, int $subjectId, int $cycleId): void { $this->calls[] = [$studentId, $subjectId, $cycleId]; }
    public function onGroupSubjectsAdded(int $groupId, int $cycleId, array $subjectIds): void { }
}
