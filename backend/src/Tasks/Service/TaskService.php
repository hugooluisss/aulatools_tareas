<?php

declare(strict_types=1);

namespace App\Tasks\Service;

use App\Auth\CurrentUser;
use App\Shared\TransactionRunner;
use App\Tasks\Repository\TaskRepository;
use App\Tasks\Repository\TaskDeliveryEventRepository;

final class TaskService
{
    public function __construct(
        private TaskRepository $repository,
        private TransactionRunner $transactions,
        private ?TaskDeliveryEventRepository $events = null,
    ) {
    }

    public function listSubjectTasks(CurrentUser $user, int $subjectId, ?int $cycleId, int $page, int $perPage): array
    {
        $this->pagination($page, $perPage);
        $subject = $this->subject($user, $subjectId);
        if ($user->role === 'teacher' && (int) $subject['teacher_id'] !== $user->id) {
            throw new TaskException('Forbidden.', 403);
        }
        if ($user->role === 'student' && !$this->repository->enrolled($subjectId, $user->id)) {
            throw new TaskException('Subject not found.', 404);
        }
        if ($cycleId !== null) {
            if ($this->repository->cycle($user->schoolId, $cycleId) === null) {
                throw new TaskException('Cycle not found.', 404);
            }
            $cycleIds = [$cycleId];
        } elseif ($user->role === 'student') {
            $cycleIds = $this->repository->studentCycles($subjectId, $user->id);
        } else {
            $cycleIds = array_map('intval', array_column($this->repository->activeCycles($user->schoolId), 'id'));
        }
        if (!in_array($user->role, ['admin', 'teacher', 'student'], true)) {
            throw new TaskException('Forbidden.', 403);
        }
        $result = $this->repository->listSubjectTasks(
            $user->schoolId,
            $user->role,
            $user->id,
            $subjectId,
            $cycleIds,
            ($page - 1) * $perPage,
            $perPage,
        );
        $result['data'] = array_map(fn (array $task): array => [
            ...$task,
            'due_at' => $task['due_at'],
            'unread_deliveries' => (int) $task['unread_deliveries'],
        ], $result['data']);
        return $this->paginated($result, $page, $perPage);
    }

    public function overview(CurrentUser $user, ?string $search, ?string $status): array
    {
        if ($user->role !== 'admin') {
            throw new TaskException('Forbidden.', 403);
        }
        $statuses = $status === null || trim($status) === '' ? [] : array_map('trim', explode(',', $status));
        foreach ($statuses as $value) {
            if (!in_array($value, ['pending', 'delivered', 'graded', 'cancelled'], true)) {
                throw new TaskException('Invalid status.', 400);
            }
        }
        $rows = $this->repository->overview(
            $user->schoolId,
            $search === null ? null : trim($search),
            $statuses,
        );
        return array_map(static fn (array $row): array => [
            'delivery_id' => (int) $row['delivery_id'],
            'status' => $row['status'],
            'task' => [
                'id' => (int) $row['task_id'],
                'title' => $row['task_title'],
                'description' => $row['task_description'],
                'due_at' => $row['due_at'],
            ],
            'student' => [
                'id' => (int) $row['student_id'],
                'first_name' => $row['student_first_name'],
                'last_name' => $row['student_last_name'],
            ],
            'subject' => [
                'id' => (int) $row['subject_id'],
                'code' => $row['subject_code'],
                'name' => $row['subject_name'],
            ],
        ], $rows);
    }

    public function deliveryStatuses(CurrentUser $user): array
    {
        if (!in_array($user->role, ['admin', 'teacher', 'student'], true)) {
            throw new TaskException('Forbidden.', 403);
        }
        return array_map(static fn (array $status): array => [
            'code' => $status['code'],
            'label' => $status['label'],
            'color' => $status['color'],
            'text_color' => $status['text_color'],
        ], $this->repository->deliveryStatuses());
    }

    public function create(CurrentUser $user, int $subjectId, array $data): array
    {
        $subject = $this->subject($user, $subjectId);
        $this->manage($user, $subject);
        $cycleId = $data['cycle_id'] ?? null;
        if (filter_var($cycleId, FILTER_VALIDATE_INT) === false || (int) $cycleId < 1) {
            throw new TaskException('Invalid cycle_id.', 400);
        }
        $cycleId = (int) $cycleId;
        $cycle = $this->repository->cycle($user->schoolId, $cycleId);
        if ($cycle === null) {
            throw new TaskException('Cycle not found.', 404);
        }
        if ($cycle['status'] !== 'active') {
            throw new TaskException('Cycle is finished.', 422);
        }
        $data = $this->validate($data);
        $id = $this->transactions->run(function () use ($user, $subjectId, $cycleId, $data): int {
            $id = $this->repository->create($subjectId, $cycleId, $data);
            $this->events?->createdForTask($id, $user);
            return $id;
        });
        return $this->find($user, $id);
    }

    public function find(CurrentUser $user, int $id): array
    {
        $task = $this->repository->find($user->schoolId, $id);
        if ($task === null) {
            throw new TaskException('Task not found.', 404);
        }
        if ($user->role === 'teacher' && (int) $task['teacher_id'] !== $user->id) {
            throw new TaskException('Forbidden.', 403);
        }
        if ($user->role === 'student' && !$this->repository->enrolled((int) $task['subject_id'], $user->id, (int) $task['cycle_id'])) {
            throw new TaskException('Task not found.', 404);
        }
        if (!in_array($user->role, ['admin', 'teacher', 'student'], true)) {
            throw new TaskException('Forbidden.', 403);
        }
        return [
            'id' => (int) $task['id'],
            'subject_id' => (int) $task['subject_id'],
            'cycle_id' => (int) $task['cycle_id'],
            'name' => $task['name'],
            'description' => $task['description'],
            'due_at' => $task['due_at'],
            'status' => $task['status'],
            'teacher' => [
                'id' => (int) $task['teacher_user_id'],
                'first_name' => $task['teacher_first_name'],
                'last_name' => $task['teacher_last_name'],
            ],
            ...($user->role === 'student'
                ? ['delivery' => $this->formatDelivery($this->repository->studentDelivery($id, $user->id))]
                : []),
        ];
    }

    public function update(CurrentUser $user, int $id, array $data): array
    {
        $task = $this->taskRow($user, $id);
        $this->manage($user, $task);
        if ($task['status'] !== 'active') {
            throw new TaskException('Cancelled tasks cannot be edited.', 422);
        }
        $data = $this->validate($data);
        $this->transactions->run(function () use ($user, $id, $data, $task): void {
            $this->repository->update($id, $data);
            foreach ($this->repository->deliveriesForTask($id) as $delivery) {
                $this->events?->add((int) $delivery['id'], $id, 'task_updated', $user, [
                    'old' => ['name' => $task['name'], 'description' => $task['description'], 'due_at' => $task['due_at']],
                    'new' => $data,
                ]);
            }
        });
        return $this->find($user, $id);
    }

    public function cancel(CurrentUser $user, int $id): array
    {
        $task = $this->taskRow($user, $id);
        $this->manage($user, $task);
        if ($task['status'] === 'cancelled') {
            throw new TaskException('Task is already cancelled.', 422);
        }
        $this->transactions->run(function () use ($id, $user, $task): void {
            $this->repository->cancel($id);
            foreach ($this->repository->deliveriesForTask($id) as $delivery) {
                $this->events?->add((int) $delivery['id'], $id, 'status_changed', $user, [
                    'old_status' => $delivery['status'],
                    'new_status' => 'cancelled',
                ]);
            }
        });
        return $this->find($user, $id);
    }

    public function deliveries(CurrentUser $user, int $id, ?string $search, ?string $status, int $page, int $perPage): array
    {
        $task = $this->taskRow($user, $id);
        $this->manage($user, $task);
        $this->pagination($page, $perPage);
        $statuses = $status === null || $status === '' ? [] : explode(',', $status);
        foreach ($statuses as $value) {
            if (!in_array($value, ['pending', 'delivered', 'graded', 'cancelled'], true)) {
                throw new TaskException('Invalid delivery status.', 400);
            }
        }
        $search = $search === null ? null : trim($search);
        $result = $this->repository->deliveries($id, $search, $statuses, ($page - 1) * $perPage, $perPage, $user->id);
        $result['data'] = array_map(fn (array $row): array => [
            'delivery' => [
                'id' => (int) $row['id'],
                'task_id' => (int) $row['task_id'],
                'student_id' => (int) $row['student_id'],
                'status' => $row['status'],
                'delivered_at' => $row['delivered_at'],
                'grade' => $row['grade'],
                'overdue' => (bool) $row['overdue'],
                'on_time' => (bool) $row['on_time'],
                'unread_comments' => (int) $row['unread_comments'],
            ],
            'student' => [
                'id' => (int) $row['student_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'enrollment_number' => $row['enrollment_number'],
            ],
        ], $result['data']);
        return $this->paginated($result, $page, $perPage);
    }

    public function markDelivered(CurrentUser $user, int $deliveryId): array
    {
        $delivery = $this->deliveryRow($user, $deliveryId);
        $this->manage($user, $delivery);
        if ($delivery['status'] !== 'pending') {
            throw new TaskException('Delivery cannot be marked delivered in its current state.', 422);
        }
        $this->transactions->run(function () use ($user, $deliveryId, $delivery): void {
            $this->repository->markDelivered($deliveryId);
            $this->events?->add($deliveryId, (int) $delivery['task_id'], 'delivered', $user, [
                'old_status' => $delivery['status'],
                'new_status' => 'delivered',
            ]);
            $this->events?->add($deliveryId, (int) $delivery['task_id'], 'status_changed', $user, [
                'old_status' => $delivery['status'],
                'new_status' => 'delivered',
            ]);
        });
        return $this->delivery($user, $deliveryId);
    }

    public function markUndelivered(CurrentUser $user, int $deliveryId): array
    {
        $delivery = $this->deliveryRow($user, $deliveryId);
        $this->manage($user, $delivery);
        if (!in_array($delivery['status'], ['delivered', 'graded'], true)) {
            throw new TaskException('Only delivered or graded deliveries can be undelivered.', 422);
        }
        $this->transactions->run(function () use ($user, $deliveryId, $delivery): void {
            $this->repository->markUndelivered($deliveryId);
            $this->events?->add($deliveryId, (int) $delivery['task_id'], 'undelivered', $user, [
                'old_status' => $delivery['status'],
                'new_status' => 'pending',
            ]);
            $this->events?->add($deliveryId, (int) $delivery['task_id'], 'status_changed', $user, [
                'old_status' => $delivery['status'],
                'new_status' => 'pending',
            ]);
        });
        return $this->delivery($user, $deliveryId);
    }

    public function grade(CurrentUser $user, int $deliveryId, array $data): array
    {
        $delivery = $this->deliveryRow($user, $deliveryId);
        $this->manage($user, $delivery);
        if (
            !isset($data['grade'])
            || !is_numeric($data['grade'])
            || (float) $data['grade'] < 0
            || (float) $data['grade'] > 100
        ) {
            throw new TaskException('Grade must be between 0 and 100.', 400);
        }
        if ($delivery['status'] !== 'delivered') {
            throw new TaskException('Only delivered tasks can be graded.', 422);
        }
        $grade = (float) $data['grade'];
        $this->transactions->run(function () use ($user, $deliveryId, $delivery, $grade): void {
            $this->repository->grade($deliveryId, $grade);
            $type = $delivery['grade'] === null ? 'graded' : 'regrade';
            $this->events?->add($deliveryId, (int) $delivery['task_id'], $type, $user, [
                'old_grade' => $delivery['grade'] === null ? null : (float) $delivery['grade'],
                'new_grade' => $grade,
            ]);
            $this->events?->add($deliveryId, (int) $delivery['task_id'], 'status_changed', $user, [
                'old_status' => $delivery['status'],
                'new_status' => 'graded',
            ]);
        });
        return $this->delivery($user, $deliveryId);
    }

    public function myTasks(CurrentUser $user, ?string $status, ?string $search, ?int $cycleId, int $page, int $perPage): array
    {
        $this->role($user, 'student');
        $this->pagination($page, $perPage);
        $statuses = $status === null || trim($status) === '' ? [] : array_map('trim', explode(',', $status));
        foreach ($statuses as $value) {
            if (!in_array($value, ['pending', 'delivered', 'graded', 'cancelled'], true)) {
                throw new TaskException('Invalid delivery status.', 400);
            }
        }
        if ($cycleId !== null && $cycleId < 1) {
            throw new TaskException('Invalid cycle_id.', 400);
        }
        $result = $this->repository->myTasks(
            $user->schoolId,
            $user->id,
            $statuses,
            $search === null ? null : trim($search),
            $cycleId,
            ($page - 1) * $perPage,
            $perPage,
        );
        $result['data'] = array_map(fn (array $row): array => [
            'task' => [
                'id' => (int) $row['task_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'due_at' => $row['due_at'],
                'status' => $row['task_status'],
            ],
            'subject' => ['id' => (int) $row['subject_id'], 'name' => $row['subject_name']],
            'delivery' => [
                'id' => (int) $row['delivery_id'],
                'status' => $row['delivery_status'],
                'delivered_at' => $this->isoTimestamp($row['delivered_at']),
                'grade' => $row['grade'] === null ? null : (float) $row['grade'],
                'overdue' => (bool) $row['overdue'],
                'on_time' => (bool) $row['on_time'],
            ],
        ], $result['data']);
        return $this->paginated($result, $page, $perPage);
    }

    public function myTaskDetail(CurrentUser $user, int $deliveryId): array
    {
        $this->role($user, 'student');
        $row = $this->repository->myTaskDetail($user->schoolId, $user->id, $deliveryId);
        if ($row === null) {
            throw new TaskException('Delivery not found.', 404);
        }
        return [
            'task' => [
                'id' => (int) $row['task_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'due_at' => $row['due_at'],
                'status' => $row['task_status'],
                'created_at' => $row['task_created_at'],
                'updated_at' => $row['task_updated_at'],
            ],
            'subject' => [
                'id' => (int) $row['subject_id'],
                'name' => $row['subject_name'],
                'teacher_name' => $row['teacher_name'],
            ],
            'teacher' => [
                'id' => (int) $row['teacher_id'],
                'first_name' => $row['teacher_first_name'],
                'last_name' => $row['teacher_last_name'],
            ],
            'delivery' => [
                'id' => (int) $row['delivery_id'],
                'status' => $row['delivery_status'],
                'delivered_at' => $this->isoTimestamp($row['delivered_at']),
                'grade' => $row['grade'] === null ? null : (float) $row['grade'],
                'overdue' => (bool) $row['overdue'],
                'on_time' => (bool) $row['on_time'],
            ],
        ];
    }

    public function history(CurrentUser $user, int $deliveryId): array
    {
        if (!in_array($user->role, ['student', 'teacher', 'admin'], true)) {
            throw new TaskException('Forbidden.', 403);
        }
        $result = $this->events?->list($user->schoolId, $deliveryId);
        if ($result === null) {
            throw new TaskException('Delivery not found.', 404);
        }
        $delivery = $result['delivery'];
        if ($user->role === 'student' && (int) $delivery['student_id'] !== $user->id) {
            throw new TaskException('Delivery not found.', 404);
        }
        if ($user->role === 'teacher' && (int) $delivery['teacher_id'] !== $user->id) {
            throw new TaskException('Forbidden.', 403);
        }
        return ['items' => array_map(static function (array $item): array {
            $payload = json_decode($item['payload'], true, 512, JSON_THROW_ON_ERROR);
            return [
                'id' => (int) $item['id'],
                'type' => $item['type'],
                'actor' => $item['actor_id'] === null ? null : [
                    'id' => (int) $item['actor_id'],
                    'name' => $item['actor_name'],
                    'role' => $item['actor_role'],
                ],
                'created_at' => (new \DateTimeImmutable($item['created_at'], new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                'payload' => (object) $payload,
            ];
        }, $result['items'])];
    }

    private function delivery(CurrentUser $user, int $id): array
    {
        $delivery = $this->deliveryRow($user, $id);
        return $this->formatDelivery($this->repository->deliveryView($user->schoolId, $id));
    }

    private function formatDelivery(?array $item): array
    {
        if ($item === null) {
            throw new TaskException('Delivery not found.', 404);
        }
        return [
            'id' => (int) $item['id'],
            'task_id' => (int) $item['task_id'],
            'student_id' => (int) $item['student_id'],
            'status' => $item['status'],
            'delivered_at' => $item['delivered_at'],
            'grade' => $item['grade'] === null ? null : (float) $item['grade'],
            'overdue' => (bool) $item['overdue'],
            'on_time' => (bool) $item['on_time'],
        ];
    }

    private function taskRow(CurrentUser $user, int $id): array
    {
        $task = $this->repository->find($user->schoolId, $id);
        if ($task === null) {
            throw new TaskException('Task not found.', 404);
        }
        return $task;
    }

    private function deliveryRow(CurrentUser $user, int $id): array
    {
        return $this->repository->delivery($user->schoolId, $id) ?? throw new TaskException('Delivery not found.', 404);
    }

    private function subject(CurrentUser $user, int $id): array
    {
        return $this->repository->subject($user->schoolId, $id) ?? throw new TaskException('Subject not found.', 404);
    }

    private function manage(CurrentUser $user, array $resource): void
    {
        if ($user->role === 'admin') {
            return;
        }
        if ($user->role !== 'teacher') {
            throw new TaskException('Forbidden.', 403);
        }
        if ((int) $resource['teacher_id'] !== $user->id) {
            throw new TaskException('Forbidden.', 403);
        }
    }

    private function role(CurrentUser $user, string $role): void
    {
        if ($user->role !== $role) {
            throw new TaskException('Forbidden.', 403);
        }
    }

    private function validate(array $data): array
    {
        foreach (['name', 'description', 'due_at'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                throw new TaskException("Invalid {$field}.", 400);
            }
        }
        try {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $data['due_at']);
        } catch (\Throwable) {
            throw new TaskException('Invalid due_at.', 400);
        }
        if ($date === false || $date->format('Y-m-d') !== $data['due_at']) {
            throw new TaskException('Invalid due_at.', 400);
        }
        return [
            'name' => trim($data['name']),
            'description' => trim($data['description']),
            'due_at' => $date->format('Y-m-d'),
        ];
    }

    private function isoTimestamp(?string $timestamp): ?string
    {
        if ($timestamp === null) {
            return null;
        }
        return (new \DateTimeImmutable($timestamp, new \DateTimeZone('UTC')))->format(DATE_ATOM);
    }

    private function pagination(int $page, int $perPage): void
    {
        if ($page < 1 || $perPage < 1 || $perPage > 100) {
            throw new TaskException('Invalid pagination.', 400);
        }
    }

    private function paginated(array $result, int $page, int $perPage): array
    {
        return \App\Shared\Paginator::build($result['data'], $result['total'], $page, $perPage);
    }
}
