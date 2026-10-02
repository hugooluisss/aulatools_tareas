<?php

declare(strict_types=1);

namespace App\Calendar\Repository;

use App\Auth\CurrentUser;
use Yiisoft\Db\Connection\ConnectionInterface;

class CalendarRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function subject(int $schoolId, int $subjectId): bool
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id FROM subjects
            INNER JOIN academic_cycles ON academic_cycles.id = subjects.cycle_id
            WHERE subjects.id = :subject_id AND academic_cycles.school_id = :school_id
            SQL, [':subject_id' => $subjectId, ':school_id' => $schoolId])->queryOne() !== null;
    }

    public function listEvents(int $schoolId, int $offset, int $limit): array
    {
        $where = 'school_id = :school_id';
        $params = [':school_id' => $schoolId, ':limit' => $limit, ':offset' => $offset];
        return $this->pageEvents($where, $params);
    }

    public function event(int $schoolId, int $id): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT id, school_id, subject_id, title, description, starts_at, ends_at
            FROM calendar_events WHERE id = :id AND school_id = :school_id
            SQL, [':id' => $id, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function createEvent(int $schoolId, array $data): int
    {
        $this->db->createCommand(
            <<<'SQL'
            INSERT INTO calendar_events (school_id, subject_id, title, description, starts_at, ends_at)
            VALUES (:school_id, :subject_id, :title, :description, :starts_at, :ends_at)
            SQL,
            [
                ':school_id' => $schoolId,
                ':subject_id' => $data['subject_id'],
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':starts_at' => $data['starts_at'],
                ':ends_at' => $data['ends_at'],
            ],
        )->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function updateEvent(int $id, array $data): void
    {
        $this->db->createCommand(
            <<<'SQL'
            UPDATE calendar_events
            SET subject_id = :subject_id, title = :title, description = :description,
                starts_at = :starts_at, ends_at = :ends_at
            WHERE id = :id
            SQL,
            [
                ':id' => $id,
                ':subject_id' => $data['subject_id'],
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':starts_at' => $data['starts_at'],
                ':ends_at' => $data['ends_at'],
            ],
        )->execute();
    }

    public function deleteEvent(int $id): void
    {
        $this->db->createCommand(<<<'SQL'
            DELETE FROM calendar_events WHERE id = :id
            SQL, [':id' => $id])->execute();
    }

    public function view(CurrentUser $user, string $from, string $to, int $offset, int $limit): array
    {
        $eventVisibility = match ($user->role) {
            'admin' => '(calendar_events.subject_id IS NULL OR event_cycles.school_id = :subject_school_id)',
            'teacher' => <<<'SQL'
                (calendar_events.subject_id IS NULL OR (
                    event_cycles.school_id = :subject_school_id
                    AND subjects.teacher_id = :event_user_id
                ))
                SQL,
            'student' => <<<'SQL'
                (calendar_events.subject_id IS NULL OR (
                    event_cycles.school_id = :subject_school_id AND EXISTS (
                        SELECT 1 FROM enrollments
                        WHERE enrollments.subject_id = subjects.id
                          AND enrollments.student_id = :event_user_id
                    )
                ))
                SQL,
            default => '1 = 0',
        };
        $taskVisibility = match ($user->role) {
            'admin' => '1 = 1',
            'teacher' => 'subjects.teacher_id = :task_user_id',
            'student' => <<<'SQL'
                EXISTS (
                    SELECT 1 FROM task_deliveries
                    WHERE task_deliveries.task_id = tasks.id
                      AND task_deliveries.student_id = :task_user_id
                      AND task_deliveries.status <> 'cancelled'
                )
                SQL,
            default => '1 = 0',
        };
        $params = [
            ':event_school_id' => $user->schoolId,
            ':event_from' => $from,
            ':event_to' => $to,
            ':task_school_id' => $user->schoolId,
            ':task_from' => $from,
            ':task_to' => $to,
            ':subject_school_id' => $user->schoolId,
            ':limit' => $limit,
            ':offset' => $offset,
        ];
        if ($user->role !== 'admin') {
            $params[':event_user_id'] = $user->id;
            $params[':task_user_id'] = $user->id;
        }
        $union = <<<SQL
            SELECT 'event' AS type, calendar_events.id, calendar_events.title, calendar_events.description,
                   calendar_events.starts_at, calendar_events.ends_at, calendar_events.subject_id, NULL AS task_id
            FROM calendar_events
            LEFT JOIN subjects ON subjects.id = calendar_events.subject_id
            LEFT JOIN academic_cycles AS event_cycles ON event_cycles.id = subjects.cycle_id
            WHERE calendar_events.school_id = :event_school_id AND calendar_events.starts_at >= :event_from
              AND calendar_events.starts_at <= :event_to AND {$eventVisibility}
            UNION ALL
            SELECT 'task_due' AS type, tasks.id, tasks.name AS title, tasks.description,
                   tasks.due_at AS starts_at, tasks.due_at AS ends_at, subjects.id AS subject_id, tasks.id AS task_id
            FROM tasks
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            INNER JOIN academic_cycles AS task_cycles ON task_cycles.id = subjects.cycle_id
            WHERE task_cycles.school_id = :task_school_id AND tasks.status = 'active'
              AND tasks.due_at >= :task_from AND tasks.due_at <= :task_to AND {$taskVisibility}
            SQL;
        $data = $this->db->createCommand(<<<SQL
            SELECT * FROM ({$union}) AS calendar_items
            ORDER BY starts_at, id LIMIT :limit OFFSET :offset
            SQL, $params)->queryAll();
        $total = (int) $this->db->createCommand(<<<SQL
            SELECT COUNT(*) FROM ({$union}) AS calendar_items
            SQL, array_diff_key($params, [':limit' => true, ':offset' => true]))->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    private function pageEvents(string $where, array $params): array
    {
        $sql = "FROM calendar_events WHERE {$where}";
        $data = $this->db->createCommand(<<<SQL
            SELECT id, school_id, subject_id, title, description, starts_at, ends_at {$sql}
            ORDER BY starts_at LIMIT :limit OFFSET :offset
            SQL, $params)->queryAll();
        $total = (int) $this->db->createCommand(<<<SQL
            SELECT COUNT(*) {$sql}
            SQL, array_diff_key($params, [':limit' => true, ':offset' => true]))->queryScalar();
        return ['data' => $data, 'total' => $total];
    }
}
