<?php

declare(strict_types=1);

namespace App\Tasks\Repository;

use App\Auth\CurrentUser;
use Yiisoft\Db\Connection\ConnectionInterface;

class TaskDeliveryEventRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function add(int $deliveryId, int $taskId, string $type, ?CurrentUser $actor, array $payload): void
    {
        $name = null;
        $role = null;
        if ($actor !== null) {
            $person = $this->db->createCommand('SELECT first_name, last_name FROM users WHERE id = :id', [':id' => $actor->id])->queryOne();
            $name = trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''));
            $role = $actor->role;
        }
        $this->db->createCommand(<<<'SQL'
            INSERT INTO task_delivery_events (delivery_id, task_id, type, actor_id, actor_name, actor_role, created_at, payload)
            VALUES (:delivery_id, :task_id, :type, :actor_id, :actor_name, :actor_role, UTC_TIMESTAMP(), :payload)
            SQL, [
                ':delivery_id' => $deliveryId,
                ':task_id' => $taskId,
                ':type' => $type,
                ':actor_id' => $actor?->id,
                ':actor_name' => $name,
                ':actor_role' => $role,
                ':payload' => json_encode((object) $payload, JSON_THROW_ON_ERROR),
            ])->execute();
    }

    public function list(int $schoolId, int $deliveryId): ?array
    {
        $delivery = $this->db->createCommand(<<<'SQL'
            SELECT task_deliveries.id, task_deliveries.student_id, subjects.teacher_id, tasks.id AS task_id
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            WHERE task_deliveries.id = :delivery_id AND subjects.school_id = :school_id
            SQL, [':delivery_id' => $deliveryId, ':school_id' => $schoolId])->queryOne();
        if ($delivery === null || $delivery === false) {
            return null;
        }
        $events = $this->db->createCommand(<<<'SQL'
            SELECT task_delivery_events.id, task_delivery_events.type, task_delivery_events.actor_id,
                   task_delivery_events.actor_name, task_delivery_events.actor_role,
                   task_delivery_events.created_at, task_delivery_events.payload
            FROM task_delivery_events
            WHERE task_delivery_events.delivery_id = :delivery_id
            ORDER BY task_delivery_events.created_at DESC, task_delivery_events.id DESC
            SQL, [':delivery_id' => $deliveryId])->queryAll();
        return ['delivery' => $delivery, 'items' => $events];
    }

    public function createdForTask(int $taskId, ?CurrentUser $actor): void
    {
        $rows = $this->db->createCommand('SELECT id FROM task_deliveries WHERE task_id = :task_id', [':task_id' => $taskId])->queryAll();
        foreach ($rows as $row) {
            $this->add((int) $row['id'], $taskId, 'task_created', $actor, []);
        }
    }

    public function recordNewForEnrollment(int $studentId, int $subjectId, int $cycleId): void
    {
        $ids = $this->db->createCommand(<<<'SQL'
            SELECT task_deliveries.id, task_deliveries.task_id
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN enrollment_subject_bindings ON enrollment_subject_bindings.subject_id = tasks.subject_id
            INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
            WHERE task_deliveries.student_id = :student_id AND tasks.subject_id = :subject_id AND tasks.cycle_id = :cycle_id
              AND enrollments.student_id = task_deliveries.student_id AND enrollments.cycle_id = tasks.cycle_id
              AND NOT EXISTS (
                  SELECT 1 FROM task_delivery_events
                  WHERE task_delivery_events.delivery_id = task_deliveries.id
                    AND task_delivery_events.type = 'task_created'
              )
            SQL, [':student_id' => $studentId, ':subject_id' => $subjectId, ':cycle_id' => $cycleId])->queryAll();
        foreach ($ids as $row) {
            $this->add((int) $row['id'], (int) $row['task_id'], 'task_created', null, []);
        }
    }

    public function recordForGroupSubject(int $groupId, int $cycleId, int $subjectId): void
    {
        $rows = $this->db->createCommand(<<<'SQL'
            SELECT task_deliveries.id, task_deliveries.task_id
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN enrollment_subject_bindings ON enrollment_subject_bindings.subject_id = tasks.subject_id
            INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
            WHERE tasks.subject_id = :subject_id AND tasks.cycle_id = :cycle_id
              AND enrollments.group_id = :group_id AND enrollments.cycle_id = :cycle_id
              AND task_deliveries.student_id = enrollments.student_id
              AND NOT EXISTS (
                  SELECT 1 FROM task_delivery_events
                  WHERE task_delivery_events.delivery_id = task_deliveries.id
                    AND task_delivery_events.type = 'task_created'
              )
            SQL, [':group_id' => $groupId, ':cycle_id' => $cycleId, ':subject_id' => $subjectId])->queryAll();
        foreach ($rows as $row) {
            $this->add((int) $row['id'], (int) $row['task_id'], 'task_created', null, []);
        }
    }
}
