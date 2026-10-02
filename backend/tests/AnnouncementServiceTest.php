<?php

declare(strict_types=1);

namespace App\Tests;

use App\Announcements\Controller\AnnouncementController;
use App\Announcements\Repository\AnnouncementRepository;
use App\Announcements\Service\AnnouncementException;
use App\Announcements\Service\AnnouncementService;
use App\Auth\CurrentUser;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;

final class AnnouncementServiceTest extends TestCase
{
    public function testStudentCanSeeActiveAnnouncementsOnly(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends AnnouncementRepository {
            public ?bool $activeOnly = null;

            public function list(int $schoolId, bool $activeOnly, int $offset, int $limit): array
            {
                $this->activeOnly = $activeOnly;
                return ['data' => [], 'total' => 0];
            }
        };
        (new AnnouncementService($repository))->active(new CurrentUser(8, 'student', 3), 1, 20);

        self::assertTrue($repository->activeOnly);
    }

    public function testEndDateMustNotPrecedeStartDate(): void
    {
        $service = new AnnouncementService($this->createMock(AnnouncementRepository::class));

        try {
            $service->create(
                new CurrentUser(1, 'admin', 3),
                [
                    'title' => 'Notice',
                    'body' => 'Body',
                    'starts_on' => '2026-10-03',
                    'ends_on' => '2026-10-02',
                ],
            );
            self::fail('Invalid announcement period was accepted.');
        } catch (AnnouncementException $exception) {
            self::assertSame(400, $exception->status);
        }
    }

    public function testStudentCannotReadAdminManagementList(): void
    {
        $controller = new AnnouncementController(
            new AnnouncementService($this->createMock(AnnouncementRepository::class)),
            new ResponseFactory(),
        );
        $request = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(8, 'student', 3));

        $response = $controller->index($request);

        self::assertSame(403, $response->getStatusCode());
    }
}
