<?php

declare(strict_types=1);

namespace App\Calendar\Service;

use App\Auth\CurrentUser;
use App\Calendar\Repository\CalendarRepository;

final class CalendarService
{
    public function __construct(private CalendarRepository $repository)
    {
    }

    public function view(CurrentUser $user, array $query): array
    {
        if (!in_array($user->role, ['admin', 'teacher', 'student'], true)) {
            throw new CalendarException('Forbidden.', 403);
        }
        $from = $this->dateTime($query['from'] ?? null, 'from');
        $to = $this->dateTime($query['to'] ?? null, 'to');
        if ($to < $from) {
            throw new CalendarException('Invalid calendar range.', 400);
        }
        [$page, $perPage] = $this->pagination($query);
        $utc = new \DateTimeZone('UTC');
        $result = $this->repository->view(
            $user,
            $from->setTimezone($utc)->format('Y-m-d H:i:s'),
            $to->setTimezone($utc)->format('Y-m-d H:i:s'),
            ($page - 1) * $perPage,
            $perPage,
        );
        $result['data'] = array_map(fn (array $item): array => $this->calendarItem($item), $result['data']);
        return [
            'data' => $result['data'],
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ];
    }

    public function listEvents(CurrentUser $user, array $query): array
    {
        $this->admin($user);
        [$page, $perPage] = $this->pagination($query);
        $result = $this->repository->listEvents($user->schoolId, ($page - 1) * $perPage, $perPage);
        $result['data'] = array_map(fn (array $item): array => $this->formatEvent($item), $result['data']);
        return [
            'data' => $result['data'],
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ];
    }

    public function event(CurrentUser $user, int $id): array
    {
        $this->admin($user);
        $row = $this->repository->event($user->schoolId, $id);
        if ($row === null) {
            throw new CalendarException('Event not found.', 404);
        }
        return $this->formatEvent($row);
    }

    public function create(CurrentUser $user, array $data): array
    {
        $this->admin($user);
        $validated = $this->validate($user, $data);
        $id = $this->repository->createEvent($user->schoolId, $validated);
        return $this->formatEvent($this->repository->event($user->schoolId, $id));
    }

    public function update(CurrentUser $user, int $id, array $data): array
    {
        $this->admin($user);
        $this->formatEvent(
            $this->repository->event($user->schoolId, $id)
                ?? throw new CalendarException('Event not found.', 404),
        );
        $this->repository->updateEvent($id, $this->validate($user, $data));
        return $this->formatEvent($this->repository->event($user->schoolId, $id));
    }

    public function delete(CurrentUser $user, int $id): void
    {
        $this->admin($user);
        $this->formatEvent(
            $this->repository->event($user->schoolId, $id)
                ?? throw new CalendarException('Event not found.', 404),
        );
        $this->repository->deleteEvent($id);
    }

    private function validate(CurrentUser $user, array $data): array
    {
        foreach (['title', 'description', 'starts_at', 'ends_at'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                throw new CalendarException("Invalid {$field}.", 400);
            }
        }
        $starts = $this->dateTime($data['starts_at'], 'starts_at');
        $ends = $this->dateTime($data['ends_at'], 'ends_at');
        if ($ends < $starts) {
            throw new CalendarException('Event end must not precede its start.', 400);
        }
        $subjectId = null;
        if (isset($data['subject_id']) && $data['subject_id'] !== '') {
            if (filter_var($data['subject_id'], FILTER_VALIDATE_INT) === false || (int) $data['subject_id'] < 1) {
                throw new CalendarException('Invalid subject_id.', 400);
            }
            $subjectId = (int) $data['subject_id'];
            if (!$this->repository->subject($user->schoolId, $subjectId)) {
                throw new CalendarException('Subject not found.', 404);
            }
        }
        return [
            'subject_id' => $subjectId,
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'starts_at' => $starts->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'ends_at' => $ends->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        ];
    }

    private function calendarItem(array $row): array
    {
        $item = [
            'type' => $row['type'],
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'starts_at' => $this->isoTimestamp($row['starts_at']),
            'ends_at' => $this->isoTimestamp($row['ends_at']),
            'subject_id' => $row['subject_id'] === null ? null : (int) $row['subject_id'],
        ];
        if ($row['task_id'] !== null) {
            $item['task_id'] = (int) $row['task_id'];
        } else {
            $item['google_calendar_url'] = $this->googleCalendarUrl($row);
        }
        return $item;
    }

    private function formatEvent(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'school_id' => (int) $row['school_id'],
            'subject_id' => $row['subject_id'] === null ? null : (int) $row['subject_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'starts_at' => $this->isoTimestamp($row['starts_at']),
            'ends_at' => $this->isoTimestamp($row['ends_at']),
        ];
    }

    private function googleCalendarUrl(array $row): string
    {
        $utc = new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable($row['starts_at'], $utc))->format('Ymd\THis\Z');
        $end = (new \DateTimeImmutable($row['ends_at'], $utc))->format('Ymd\THis\Z');
        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action' => 'TEMPLATE',
            'text' => $row['title'],
            'details' => $row['description'],
            'dates' => $start . '/' . $end,
        ]);
    }

    private function dateTime(mixed $value, string $field): \DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            throw new CalendarException("Invalid {$field}.", 400);
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            throw new CalendarException("Invalid {$field}.", 400);
        }
    }

    private function isoTimestamp(string $timestamp): string
    {
        return (new \DateTimeImmutable($timestamp, new \DateTimeZone('UTC')))->format(DATE_ATOM);
    }

    private function pagination(array $query): array
    {
        $page = filter_var($query['page'] ?? 1, FILTER_VALIDATE_INT);
        $perPage = filter_var($query['per_page'] ?? 20, FILTER_VALIDATE_INT);
        if ($page === false || $perPage === false || $page < 1 || $perPage < 1 || $perPage > 100) {
            throw new CalendarException('Invalid pagination.', 400);
        }
        return [(int) $page, (int) $perPage];
    }

    private function admin(CurrentUser $user): void
    {
        if ($user->role !== 'admin') {
            throw new CalendarException('Forbidden.', 403);
        }
    }
}
