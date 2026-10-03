<?php

declare(strict_types=1);

namespace App\Tasks\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class TaskRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function subject(int $schoolId, int $subjectId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id, subjects.teacher_id, subjects.school_id
            FROM subjects
            WHERE subjects.id = :subject_id AND subjects.school_id = :school_id
            SQL, [':subject_id' => $subjectId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function enrolled(int $subjectId, int $studentId, ?int $cycleId = null): bool
    {
        $cycle = $cycleId === null ? '' : ' AND enrollments.cycle_id = :cycle_id';
        $params = [':subject_id' => $subjectId, ':student_id' => $studentId];
        if ($cycleId !== null) {
            $params[':cycle_id'] = $cycleId;
        }
        return $this->db->createCommand(<<<'SQL'
            SELECT enrollments.student_id
            FROM enrollment_subject_bindings
            INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
            WHERE enrollment_subject_bindings.subject_id = :subject_id AND enrollments.student_id = :student_id
            SQL . $cycle, $params)->queryOne() !== null;
    }

    public function cycle(int $schoolId, int $cycleId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT id, status FROM academic_cycles WHERE id = :id AND school_id = :school_id
            SQL, [':id' => $cycleId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function activeCycles(int $schoolId): array
    {
        return $this->db->createCommand(
            "SELECT id FROM academic_cycles WHERE school_id = :school_id AND status = 'active'",
            [':school_id' => $schoolId],
        )->queryAll();
    }

    public function studentCycles(int $subjectId, int $studentId): array
    {
        return array_map('intval', array_column($this->db->createCommand(<<<'SQL'
            SELECT enrollments.cycle_id
            FROM enrollment_subject_bindings
            INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
            WHERE enrollment_subject_bindings.subject_id = :subject_id AND enrollments.student_id = :student_id
            SQL, [':subject_id' => $subjectId, ':student_id' => $studentId])->queryAll(), 'cycle_id'));
    }

    public function studentDelivery(int $taskId, int $studentId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT task_deliveries.id, task_deliveries.task_id, task_deliveries.student_id,
                   task_deliveries.status, task_deliveries.delivered_at, task_deliveries.grade,
            (task_deliveries.status = 'pending' AND tasks.due_at < UTC_TIMESTAMP()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND task_deliveries.delivered_at <= tasks.due_at
                   ) AS on_time
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            WHERE task_deliveries.task_id = :task_id AND task_deliveries.student_id = :student_id
            SQL, [':task_id' => $taskId, ':student_id' => $studentId])->queryOne() ?: null;
    }

    public function create(int $subjectId, int $cycleId, array $data): int
    {
        $this->db->createCommand(
            <<<'SQL'
            INSERT INTO tasks (subject_id, cycle_id, name, description, due_at)
            VALUES (:subject_id, :cycle_id, :name, :description, :due_at)
            SQL,
            [
                ':subject_id' => $subjectId,
                ':cycle_id' => $cycleId,
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':due_at' => $data['due_at'],
            ],
        )->execute();
        $id = (int) $this->db->getLastInsertId();
        $this->db->createCommand(<<<'SQL'
            INSERT INTO task_deliveries (task_id, student_id)
            SELECT :task_id, enrollments.student_id
            FROM enrollments
            INNER JOIN enrollment_subject_bindings ON enrollment_subject_bindings.enrollment_id = enrollments.id
            WHERE enrollment_subject_bindings.subject_id = :subject_id AND enrollments.cycle_id = :cycle_id
            SQL, [':task_id' => $id, ':subject_id' => $subjectId, ':cycle_id' => $cycleId])->execute();
        return $id;
    }

    public function find(int $schoolId, int $taskId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT tasks.id, tasks.subject_id, tasks.name, tasks.description, tasks.due_at, tasks.status,
                   subjects.teacher_id, subjects.school_id, tasks.cycle_id,
                   teachers.id AS teacher_user_id, teachers.first_name AS teacher_first_name,
                   teachers.last_name AS teacher_last_name
            FROM tasks
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            INNER JOIN users AS teachers ON teachers.id = subjects.teacher_id
            WHERE tasks.id = :task_id AND subjects.school_id = :school_id
            SQL, [':task_id' => $taskId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function update(int $taskId, array $data): void
    {
        $this->db->createCommand(
            <<<'SQL'
            UPDATE tasks SET name = :name, description = :description, due_at = :due_at
            WHERE id = :task_id
            SQL,
            [
                ':task_id' => $taskId,
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':due_at' => $data['due_at'],
            ],
        )->execute();
    }

    public function cancel(int $taskId): void
    {
        $this->db->createCommand(<<<'SQL'
            UPDATE tasks SET status = 'cancelled' WHERE id = :task_id
            SQL, [':task_id' => $taskId])->execute();
        $this->db->createCommand(<<<'SQL'
            UPDATE task_deliveries SET status = 'cancelled' WHERE task_id = :task_id
            SQL, [':task_id' => $taskId])->execute();
    }

    public function listSubjectTasks(
        int $schoolId,
        string $role,
        int $userId,
        int $subjectId,
        array $cycleIds,
        int $offset,
        int $limit,
    ): array {
        if ($cycleIds === []) {
            return ['data' => [], 'total' => 0];
        }
        $visibility = match ($role) {
            'teacher' => ' AND subjects.teacher_id = :user_id',
            'student' => <<<'SQL'
                AND EXISTS (
                    SELECT 1 FROM enrollment_subject_bindings
                    INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
                    WHERE enrollment_subject_bindings.subject_id = subjects.id AND enrollments.cycle_id = tasks.cycle_id
                      AND enrollments.student_id = :user_id
                )
                SQL,
            default => '',
        };
        $params = [':school_id' => $schoolId, ':subject_id' => $subjectId, ':limit' => $limit, ':offset' => $offset];
        $cycleParams = [];
        $cycleNames = [];
        foreach (array_values($cycleIds) as $index => $cycle) {
            $name = ':cycle' . $index;
            $cycleNames[] = $name;
            $cycleParams[$name] = $cycle;
        }
        $params += $cycleParams;
        if ($role !== 'admin') {
            $params[':user_id'] = $userId;
        }
        $sql = 'FROM tasks INNER JOIN subjects ON subjects.id = tasks.subject_id '
            . 'WHERE subjects.school_id = :school_id AND subjects.id = :subject_id '
            . 'AND tasks.cycle_id IN (' . implode(', ', $cycleNames) . ')' . $visibility;
        $data = $this->db->createCommand(<<<SQL
            SELECT tasks.id, tasks.subject_id, tasks.name, tasks.description, tasks.due_at, tasks.status
            {$sql}
            ORDER BY tasks.due_at LIMIT :limit OFFSET :offset
            SQL, $params)->queryAll();
        $total = (int) $this->db->createCommand(<<<SQL
            SELECT COUNT(*) {$sql}
            SQL, array_diff_key($params, [':limit' => true, ':offset' => true]))->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    public function deliveries(int $taskId, ?string $status, int $offset, int $limit): array
    {
        $statusClause = $status === null ? '' : ' AND task_deliveries.status = :status';
        $params = [':task_id' => $taskId, ':limit' => $limit, ':offset' => $offset];
        if ($status !== null) {
            $params[':status'] = $status;
        }
        $sql = <<<SQL
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN users AS students ON students.id = task_deliveries.student_id
            INNER JOIN students AS student_profiles ON student_profiles.user_id = students.id
            WHERE task_deliveries.task_id = :task_id{$statusClause}
            SQL;
        $data = $this->db->createCommand(<<<SQL
            SELECT task_deliveries.id, task_deliveries.task_id, task_deliveries.student_id, task_deliveries.status,
                   task_deliveries.delivered_at, task_deliveries.grade,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_TIMESTAMP()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND task_deliveries.delivered_at <= tasks.due_at
                   ) AS on_time,
                   students.first_name, students.last_name, student_profiles.enrollment_number
            {$sql}
            ORDER BY students.last_name, students.first_name
            LIMIT :limit OFFSET :offset
            SQL, $params)->queryAll();
        $total = (int) $this->db->createCommand(<<<SQL
            SELECT COUNT(*) {$sql}
            SQL, array_diff_key($params, [':limit' => true, ':offset' => true]))->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    public function delivery(int $schoolId, int $deliveryId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT task_deliveries.id, task_deliveries.task_id, task_deliveries.student_id, task_deliveries.status,
                   task_deliveries.delivered_at, task_deliveries.grade, tasks.due_at,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_TIMESTAMP()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND task_deliveries.delivered_at <= tasks.due_at
                   ) AS on_time,
                   subjects.id AS subject_id, subjects.teacher_id, subjects.school_id
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            WHERE task_deliveries.id = :delivery_id AND subjects.school_id = :school_id
            SQL, [':delivery_id' => $deliveryId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function markDelivered(int $deliveryId): void
    {
        $this->db->createCommand(<<<'SQL'
            UPDATE task_deliveries
            SET status = 'delivered', delivered_at = UTC_TIMESTAMP(), grade = NULL
            WHERE id = :id
            SQL, [':id' => $deliveryId])->execute();
    }

    public function deliveryView(int $schoolId, int $deliveryId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT task_deliveries.id, task_deliveries.task_id, task_deliveries.student_id,
                   task_deliveries.status, task_deliveries.delivered_at, task_deliveries.grade,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_TIMESTAMP()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND task_deliveries.delivered_at <= tasks.due_at
                   ) AS on_time
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            WHERE task_deliveries.id = :delivery_id AND subjects.school_id = :school_id
            SQL, [':delivery_id' => $deliveryId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function grade(int $deliveryId, int|float $grade): void
    {
        $this->db->createCommand(<<<'SQL'
            UPDATE task_deliveries SET status = 'graded', grade = :grade WHERE id = :id
            SQL, [':id' => $deliveryId, ':grade' => $grade])->execute();
    }

    public function myTasks(int $schoolId, int $studentId, string $status, int $offset, int $limit): array
    {
        $sql = <<<'SQL'
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            WHERE task_deliveries.student_id = :student_id AND subjects.school_id = :school_id
              AND task_deliveries.status = :status
            SQL;
        $params = [':student_id' => $studentId, ':school_id' => $schoolId, ':status' => $status];
        $data = $this->db->createCommand(<<<SQL
            SELECT tasks.id AS task_id, tasks.name, tasks.description, tasks.due_at, tasks.status AS task_status,
                   subjects.id AS subject_id, subjects.name AS subject_name,
                   task_deliveries.id AS delivery_id, task_deliveries.status AS delivery_status,
                   task_deliveries.delivered_at, task_deliveries.grade,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_TIMESTAMP()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND task_deliveries.delivered_at <= tasks.due_at
                   ) AS on_time
            {$sql}
            ORDER BY tasks.due_at LIMIT :limit OFFSET :offset
            SQL, $params + [':limit' => $limit, ':offset' => $offset])->queryAll();
        $total = (int) $this->db->createCommand(<<<SQL
            SELECT COUNT(*) {$sql}
            SQL, $params)->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    public function myTaskDetail(int $schoolId, int $studentId, int $deliveryId): ?array
    {
        return $this->db->createCommand(
            <<<'SQL'
            SELECT tasks.id AS task_id, tasks.name, tasks.description, tasks.due_at, tasks.status AS task_status,
                   subjects.id AS subject_id, subjects.name AS subject_name,
                   task_deliveries.id AS delivery_id, task_deliveries.status AS delivery_status,
                   task_deliveries.delivered_at, task_deliveries.grade,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_TIMESTAMP()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND task_deliveries.delivered_at <= tasks.due_at
                   ) AS on_time,
                   teachers.id AS teacher_id, teachers.first_name AS teacher_first_name,
                   teachers.last_name AS teacher_last_name
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            INNER JOIN users AS teachers ON teachers.id = subjects.teacher_id
            WHERE task_deliveries.id = :delivery_id AND task_deliveries.student_id = :student_id
              AND subjects.school_id = :school_id
            SQL,
            [
                ':delivery_id' => $deliveryId,
                ':student_id' => $studentId,
                ':school_id' => $schoolId,
            ],
        )->queryOne() ?: null;
    }

    public function createForEnrollment(int $studentId, int $subjectId, int $cycleId): void
    {
        $this->db->createCommand(<<<'SQL'
            INSERT INTO task_deliveries (task_id, student_id)
            SELECT tasks.id, :student_id
            FROM tasks
            WHERE tasks.subject_id = :subject_id AND tasks.cycle_id = :cycle_id AND tasks.status = 'active'
            ON DUPLICATE KEY UPDATE student_id = VALUES(student_id)
            SQL, [':student_id' => $studentId, ':subject_id' => $subjectId, ':cycle_id' => $cycleId])->execute();
    }

    public function createForGroupSubjects(int $groupId, int $cycleId, array $subjectIds): void
    {
        if ($subjectIds === []) {
            return;
        }
        [$in, $params] = $this->inParams($subjectIds, 'subject');
        $params[':group_id'] = $groupId;
        $params[':cycle_id'] = $cycleId;
        $this->db->createCommand(<<<SQL
            INSERT IGNORE INTO task_deliveries (task_id, student_id)
            SELECT tasks.id, enrollments.student_id
            FROM tasks
            INNER JOIN enrollment_subject_bindings ON enrollment_subject_bindings.subject_id = tasks.subject_id
            INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
            WHERE tasks.cycle_id = :cycle_id AND tasks.status = 'active'
              AND enrollments.group_id = :group_id AND enrollments.cycle_id = :cycle_id
              AND tasks.subject_id IN ({$in})
            SQL, $params)->execute();
    }

    private function inParams(array $values, string $prefix): array
    {
        $holders = [];
        $params = [];
        foreach (array_values($values) as $index => $value) {
            $name = ':' . $prefix . $index;
            $holders[] = $name;
            $params[$name] = $value;
        }
        return [implode(', ', $holders), $params];
    }
}
