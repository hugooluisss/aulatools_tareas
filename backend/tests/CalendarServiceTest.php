<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Calendar\Controller\CalendarController;
use App\Calendar\Repository\CalendarRepository;
use App\Calendar\Service\CalendarException;
use App\Calendar\Service\CalendarService;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;

final class CalendarServiceTest extends TestCase
{
    public function testOnlyAdminCanCreateEvents(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CalendarRepository {
            public int $created = 0;

            public function createEvent(int $schoolId, array $data): int
            {
                $this->created++;
                return 5;
            }

            public function event(int $schoolId, int $id): ?array
            {
                return [
                    'id' => $id,
                    'school_id' => $schoolId,
                    'subject_id' => null,
                    'title' => 'Assembly',
                    'description' => '',
                    'starts_at' => '2026-10-02 12:00:00',
                    'ends_at' => '2026-10-02 13:00:00',
                ];
            }
        };
        $service = new CalendarService($repository);

        try {
            $service->create(
                new CurrentUser(7, 'teacher', 2),
                [
                    'title' => 'Assembly',
                    'description' => '',
                    'starts_at' => '2026-10-02T12:00:00Z',
                    'ends_at' => '2026-10-02T13:00:00Z',
                ],
            );
            self::fail('Teacher created a calendar event.');
        } catch (CalendarException $exception) {
            self::assertSame(403, $exception->status);
        }

        self::assertSame(0, $repository->created);
    }

    public function testCalendarRejectsSubjectFromAnotherSchool(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CalendarRepository {
            public function subject(int $schoolId, int $subjectId): bool
            {
                return false;
            }
        };

        try {
            (new CalendarService($repository))->create(
                new CurrentUser(1, 'admin', 2),
                [
                    'subject_id' => 90,
                    'title' => 'Event',
                    'description' => 'Details',
                    'starts_at' => '2026-10-02T12:00:00Z',
                    'ends_at' => '2026-10-02T13:00:00Z',
                ],
            );
            self::fail('Cross-school subject was accepted.');
        } catch (CalendarException $exception) {
            self::assertSame(404, $exception->status);
        }
    }

    public function testInvalidCalendarRangeReturns400(): void
    {
        $service = new CalendarService($this->createMock(CalendarRepository::class));
        $controller = new CalendarController($service, new ResponseFactory());
        $request = (new ServerRequest())
            ->withAttribute(CurrentUser::class, new CurrentUser(2, 'student', 2))
            ->withQueryParams([
                'from' => '2026-10-04T00:00:00Z',
                'to' => '2026-10-02T00:00:00Z',
            ]);

        $response = $controller->view($request);

        self::assertSame(400, $response->getStatusCode());
    }

    public function testCombinedViewIncludesEventsAndTaskDueDates(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CalendarRepository {
            public function view(CurrentUser $user, string $from, string $to, int $offset, int $limit): array
            {
                return ['data' => [
                    [
                        'type' => 'event',
                        'id' => 4,
                        'title' => 'Assembly',
                        'description' => 'All school',
                        'starts_at' => '2026-10-02 12:00:00',
                        'ends_at' => '2026-10-02 13:00:00',
                        'subject_id' => null,
                        'task_id' => null,
                    ],
                    [
                        'type' => 'task_due',
                        'id' => 9,
                        'title' => 'Essay',
                        'description' => 'Write',
                        'starts_at' => '2026-10-03 12:00:00',
                        'ends_at' => '2026-10-03 12:00:00',
                        'subject_id' => 5,
                        'task_id' => 9,
                    ],
                ], 'total' => 2];
            }
        };
        $result = (new CalendarService($repository))->view(
            new CurrentUser(2, 'student', 2),
            ['from' => '2026-10-01T00:00:00Z', 'to' => '2026-10-31T23:59:59Z'],
        );

        self::assertSame('event', $result['data'][0]['type']);
        self::assertStringContainsString('calendar.google.com', $result['data'][0]['google_calendar_url']);
        self::assertSame(9, $result['data'][1]['task_id']);
    }
}
