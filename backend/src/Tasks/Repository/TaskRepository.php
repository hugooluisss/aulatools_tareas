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
            (task_deliveries.status = 'pending' AND tasks.due_at < UTC_DATE()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND DATE(task_deliveries.delivered_at) <= tasks.due_at
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

    public function deliveriesForTask(int $taskId): array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT id, status FROM task_deliveries WHERE task_id = :task_id
            SQL, [':task_id' => $taskId])->queryAll();
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
            SELECT tasks.id, tasks.subject_id, tasks.name, tasks.description, tasks.due_at, tasks.status,
                   CASE WHEN :is_student = 1 THEN 0 ELSE (
                       SELECT COUNT(DISTINCT task_deliveries.id)
                       FROM task_deliveries
                       INNER JOIN task_comments ON task_comments.delivery_id = task_deliveries.id
                       INNER JOIN users AS comment_authors ON comment_authors.id = task_comments.author_id
                       LEFT JOIN delivery_comment_reads ON delivery_comment_reads.delivery_id = task_deliveries.id
                           AND delivery_comment_reads.user_id = :task_reader_id
                       WHERE task_deliveries.task_id = tasks.id AND comment_authors.role = 'student'
                         AND task_comments.author_id <> :comment_reader_id
                         AND (delivery_comment_reads.last_read_comment_id IS NULL
                              OR task_comments.id > delivery_comment_reads.last_read_comment_id)
                   ) END AS unread_deliveries
            {$sql}
            ORDER BY tasks.due_at LIMIT :limit OFFSET :offset
            SQL, $params + [
                ':is_student' => (int) ($role === 'student'),
                ':task_reader_id' => $userId,
                ':comment_reader_id' => $userId,
            ])->queryAll();
        $total = (int) $this->db->createCommand(<<<SQL
            SELECT COUNT(*) {$sql}
            SQL, array_diff_key($params, [':limit' => true, ':offset' => true]))->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    public function deliveries(int $taskId, ?string $search, array $statuses, int $offset, int $limit, int $userId): array
    {
        $filters = '';
        $params = [':task_id' => $taskId, ':limit' => $limit, ':offset' => $offset];
        if ($statuses !== []) {
            $names = [];
            foreach ($statuses as $index => $status) {
                $name = ':status' . $index;
                $names[] = $name;
                $params[$name] = $status;
            }
            $filters .= ' AND task_deliveries.status IN (' . implode(', ', $names) . ')';
        }
        if ($search !== null && trim($search) !== '') {
            $filters .= ' AND (students.first_name LIKE :first_name_search OR students.last_name LIKE :last_name_search OR student_profiles.enrollment_number LIKE :enrollment_search)';
            $searchTerm = '%' . trim($search) . '%';
            $params[':first_name_search'] = $searchTerm;
            $params[':last_name_search'] = $searchTerm;
            $params[':enrollment_search'] = $searchTerm;
        }
        $sql = <<<SQL
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN users AS students ON students.id = task_deliveries.student_id
            INNER JOIN students AS student_profiles ON student_profiles.user_id = students.id
            WHERE task_deliveries.task_id = :task_id{$filters}
            SQL;
        $data = $this->db->createCommand(<<<SQL
            SELECT task_deliveries.id, task_deliveries.task_id, task_deliveries.student_id, task_deliveries.status,
                   task_deliveries.delivered_at, task_deliveries.grade,
                   (SELECT COUNT(*)
                    FROM task_comments
                    INNER JOIN users AS comment_authors ON comment_authors.id = task_comments.author_id
                    LEFT JOIN delivery_comment_reads ON delivery_comment_reads.delivery_id = task_deliveries.id
                        AND delivery_comment_reads.user_id = :read_user_id
                    WHERE task_comments.delivery_id = task_deliveries.id AND comment_authors.role = 'student'
                      AND task_comments.author_id <> :author_user_id
                      AND (delivery_comment_reads.last_read_comment_id IS NULL
                           OR task_comments.id > delivery_comment_reads.last_read_comment_id)
                   ) AS unread_comments,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_DATE()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND DATE(task_deliveries.delivered_at) <= tasks.due_at
                   ) AS on_time,
                   students.first_name, students.last_name, student_profiles.enrollment_number
            {$sql}
            ORDER BY students.last_name, students.first_name
            LIMIT :limit OFFSET :offset
            SQL, $params + [':read_user_id' => $userId, ':author_user_id' => $userId])->queryAll();
        $total = (int) $this->db->createCommand(<<<SQL
            SELECT COUNT(*) {$sql}
            SQL, array_diff_key($params, [':limit' => true, ':offset' => true]))->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    public function overview(int $schoolId, ?string $search, array $statuses): array
    {
        $filters = '';
        $params = [':school_id' => $schoolId];
        if ($search !== null && $search !== '') {
            $filters .= <<<'SQL'
                AND (
                    students.first_name LIKE :search
                    OR students.last_name LIKE :search
                    OR subjects.code LIKE :search
                    OR subjects.name LIKE :search
                )
                SQL;
            $params[':search'] = '%' . $search . '%';
        }
        if ($statuses !== []) {
            $placeholders = [];
            foreach ($statuses as $index => $status) {
                $placeholder = ':status_' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $status;
            }
            $filters .= ' AND task_deliveries.status IN (' . implode(', ', $placeholders) . ')';
        }
        $joins = <<<'SQL'
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            INNER JOIN users AS students ON students.id = task_deliveries.student_id
            INNER JOIN students AS student_profiles ON student_profiles.user_id = students.id
            WHERE subjects.school_id = :school_id
              AND subjects.status = 'active'
              AND students.role = 'student'
              AND student_profiles.status = 'active'
              AND EXISTS (
                  SELECT 1
                  FROM enrollment_subject_bindings
                  INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
                  INNER JOIN academic_cycles ON academic_cycles.id = enrollments.cycle_id
                  WHERE enrollment_subject_bindings.subject_id = subjects.id
                    AND enrollments.student_id = students.id
                    AND enrollments.cycle_id = tasks.cycle_id
                    AND academic_cycles.status = 'active'
              )
            SQL;
        $data = $this->db->createCommand(<<<SQL
            SELECT task_deliveries.id AS delivery_id,
                   task_deliveries.status,
                   tasks.id AS task_id,
                   tasks.name AS task_title,
                   tasks.description AS task_description,
                   tasks.due_at,
                   students.id AS student_id,
                   students.first_name AS student_first_name,
                   students.last_name AS student_last_name,
                   subjects.id AS subject_id,
                   subjects.code AS subject_code,
                   subjects.name AS subject_name
            {$joins}{$filters}
            ORDER BY tasks.due_at ASC, task_deliveries.id ASC
            SQL, $params)->queryAll();
        return $data;
    }

    public function deliveryStatuses(): array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT code, label, color, text_color
            FROM task_delivery_statuses
            ORDER BY sort_order
            SQL)->queryAll();
    }

    public function delivery(int $schoolId, int $deliveryId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT task_deliveries.id, task_deliveries.task_id, task_deliveries.student_id, task_deliveries.status,
                   task_deliveries.delivered_at, task_deliveries.grade, tasks.due_at,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_DATE()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND DATE(task_deliveries.delivered_at) <= tasks.due_at
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

    public function markUndelivered(int $deliveryId): void
    {
        $this->db->createCommand(<<<'SQL'
            UPDATE task_deliveries
            SET status = 'pending', delivered_at = NULL, grade = NULL
            WHERE id = :id
            SQL, [':id' => $deliveryId])->execute();
    }

    public function deliveryView(int $schoolId, int $deliveryId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT task_deliveries.id, task_deliveries.task_id, task_deliveries.student_id,
                   task_deliveries.status, task_deliveries.delivered_at, task_deliveries.grade,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_DATE()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND DATE(task_deliveries.delivered_at) <= tasks.due_at
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

    public function myTasks(int $schoolId, int $studentId, array $statuses, ?string $search, ?int $cycleId, int $offset, int $limit): array
    {
        $filters = '';
        $params = [':student_id' => $studentId, ':school_id' => $schoolId];
        if ($statuses !== []) {
            $names = [];
            foreach ($statuses as $index => $status) {
                $name = ':status' . $index;
                $names[] = $name;
                $params[$name] = $status;
            }
            $filters .= ' AND task_deliveries.status IN (' . implode(', ', $names) . ')';
        }
        if ($search !== null && $search !== '') {
            $filters .= ' AND (tasks.name LIKE :task_search OR subjects.name LIKE :subject_search)';
            $params[':task_search'] = '%' . $search . '%';
            $params[':subject_search'] = '%' . $search . '%';
        }
        if ($cycleId !== null) {
            $filters .= ' AND tasks.cycle_id = :cycle_id';
            $params[':cycle_id'] = $cycleId;
        }
        $sql = <<<'SQL'
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            WHERE task_deliveries.student_id = :student_id AND subjects.school_id = :school_id
            SQL;
        $sql .= $filters;
        $data = $this->db->createCommand(<<<SQL
            SELECT tasks.id AS task_id, tasks.name, tasks.description, tasks.due_at, tasks.status AS task_status,
                   NULL AS task_created_at, NULL AS task_updated_at,
                   subjects.id AS subject_id, subjects.name AS subject_name,
                   task_deliveries.id AS delivery_id, task_deliveries.status AS delivery_status,
                   task_deliveries.delivered_at, task_deliveries.grade,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_DATE()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND DATE(task_deliveries.delivered_at) <= tasks.due_at
                   ) AS on_time
            {$sql}
            ORDER BY tasks.due_at ASC, task_deliveries.id ASC LIMIT :limit OFFSET :offset
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
                   (SELECT MIN(created_at) FROM task_delivery_events WHERE delivery_id = task_deliveries.id AND type = 'task_created') AS task_created_at,
                   (SELECT MAX(created_at) FROM task_delivery_events WHERE delivery_id = task_deliveries.id AND type IN ('task_updated', 'status_changed')) AS task_updated_at,
                   subjects.id AS subject_id, subjects.name AS subject_name,
                   task_deliveries.id AS delivery_id, task_deliveries.status AS delivery_status,
                   task_deliveries.delivered_at, task_deliveries.grade,
                   (task_deliveries.status = 'pending' AND tasks.due_at < UTC_DATE()) AS overdue,
                   (
                       task_deliveries.delivered_at IS NOT NULL
                       AND DATE(task_deliveries.delivered_at) <= tasks.due_at
                   ) AS on_time,
                   teachers.id AS teacher_id, teachers.first_name AS teacher_first_name,
                   teachers.last_name AS teacher_last_name,
                   NULLIF(TRIM(CONCAT_WS(' ', teachers.first_name, teachers.last_name)), '') AS teacher_name
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
