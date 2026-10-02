<?php

declare(strict_types=1);

namespace App\Tasks\Service;

use App\Auth\CurrentUser;
use App\Shared\TransactionRunner;
use App\Tasks\Repository\TaskRepository;

final class TaskService
{
    public function __construct(private TaskRepository $repository, private TransactionRunner $transactions)
    {
    }

    public function listSubjectTasks(CurrentUser $user, int $subjectId, int $page, int $perPage): array
    {
        $this->pagination($page, $perPage);
        $subject = $this->subject($user, $subjectId);
        if ($user->role === 'teacher' && (int) $subject['teacher_id'] !== $user->id) {
            throw new TaskException('Forbidden.', 403);
        }
        if ($user->role === 'student' && !$this->repository->enrolled($subjectId, $user->id)) {
            throw new TaskException('Subject not found.', 404);
        }
        if (!in_array($user->role, ['admin', 'teacher', 'student'], true)) {
            throw new TaskException('Forbidden.', 403);
        }
        $result = $this->repository->listSubjectTasks(
            $user->schoolId,
            $user->role,
            $user->id,
            $subjectId,
            ($page - 1) * $perPage,
            $perPage,
        );
        $result['data'] = array_map(fn (array $task): array => [
            ...$task,
            'due_at' => $this->isoTimestamp($task['due_at']),
        ], $result['data']);
        return $this->paginated($result, $page, $perPage);
    }

    public function create(CurrentUser $user, int $subjectId, array $data): array
    {
        $subject = $this->subject($user, $subjectId);
        $this->manage($user, $subject);
        $data = $this->validate($data);
        $id = $this->transactions->run(fn (): int => $this->repository->create($subjectId, $data));
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
        if ($user->role === 'student' && !$this->repository->enrolled((int) $task['subject_id'], $user->id)) {
            throw new TaskException('Task not found.', 404);
        }
        if (!in_array($user->role, ['admin', 'teacher', 'student'], true)) {
            throw new TaskException('Forbidden.', 403);
        }
        return [
            'id' => (int) $task['id'],
            'subject_id' => (int) $task['subject_id'],
            'name' => $task['name'],
            'description' => $task['description'],
            'due_at' => $this->isoTimestamp($task['due_at']),
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
        $this->repository->update($id, $this->validate($data));
        return $this->find($user, $id);
    }

    public function cancel(CurrentUser $user, int $id): array
    {
        $task = $this->taskRow($user, $id);
        $this->manage($user, $task);
        if ($task['status'] === 'cancelled') {
            throw new TaskException('Task is already cancelled.', 422);
        }
        $this->transactions->run(function () use ($id): void {
            $this->repository->cancel($id);
        });
        return $this->find($user, $id);
    }

    public function deliveries(CurrentUser $user, int $id, ?string $status, int $page, int $perPage): array
    {
        $task = $this->taskRow($user, $id);
        $this->manage($user, $task);
        $this->pagination($page, $perPage);
        if ($status !== null && !in_array($status, ['pending', 'delivered', 'graded', 'cancelled'], true)) {
            throw new TaskException('Invalid delivery status.', 400);
        }
        $result = $this->repository->deliveries($id, $status, ($page - 1) * $perPage, $perPage);
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
        $this->repository->markDelivered($deliveryId);
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
        $this->repository->grade($deliveryId, $grade);
        return $this->delivery($user, $deliveryId);
    }

    public function myTasks(CurrentUser $user, ?string $status, int $page, int $perPage): array
    {
        $this->role($user, 'student');
        $this->pagination($page, $perPage);
        $status ??= 'pending';
        if (!in_array($status, ['pending', 'delivered', 'graded', 'cancelled'], true)) {
            throw new TaskException('Invalid delivery status.', 400);
        }
        $result = $this->repository->myTasks($user->schoolId, $user->id, $status, ($page - 1) * $perPage, $perPage);
        $result['data'] = array_map(fn (array $row): array => [
            'task' => [
                'id' => (int) $row['task_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'due_at' => $this->isoTimestamp($row['due_at']),
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
                'due_at' => $this->isoTimestamp($row['due_at']),
                'status' => $row['task_status'],
            ],
            'subject' => ['id' => (int) $row['subject_id'], 'name' => $row['subject_name']],
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
            $date = new \DateTimeImmutable($data['due_at']);
        } catch (\Throwable) {
            throw new TaskException('Invalid due_at.', 400);
        }
        return [
            'name' => trim($data['name']),
            'description' => trim($data['description']),
            'due_at' => $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
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
        return [
            'data' => $result['data'],
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ];
    }
}
