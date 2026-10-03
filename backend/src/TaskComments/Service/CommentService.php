<?php

declare(strict_types=1);

namespace App\TaskComments\Service;

use App\Auth\CurrentUser;
use App\TaskComments\Repository\CommentRepository;

final class CommentService
{
    public function __construct(private CommentRepository $repository)
    {
    }

    public function list(CurrentUser $user, int $deliveryId, int $page, int $perPage): array
    {
        $this->access($user, $deliveryId);
        $this->pagination($page, $perPage);
        $result = $this->repository->list($deliveryId, ($page - 1) * $perPage, $perPage);
        $result['data'] = array_map([$this, 'format'], $result['data']);
        return \App\Shared\Paginator::build($result['data'], $result['total'], $page, $perPage);
    }

    public function create(CurrentUser $user, int $deliveryId, array $data): array
    {
        $this->access($user, $deliveryId);
        $body = $data['body'] ?? null;
        if (!is_string($body) || trim($body) === '' || mb_strlen(trim($body), 'UTF-8') > 2000) {
            throw new CommentException('Comment must contain 1 to 2000 characters.', 400);
        }
        $id = $this->repository->create($deliveryId, $user->id, trim($body));
        return $this->format($this->repository->find($id));
    }

    private function access(CurrentUser $user, int $deliveryId): void
    {
        $delivery = $this->repository->delivery($user->schoolId, $deliveryId);
        if ($delivery === null) {
            throw new CommentException('Delivery not found.', 404);
        }
        if ($user->role === 'admin') {
            return;
        }
        if ($user->role === 'teacher' && (int) $delivery['teacher_id'] === $user->id) {
            return;
        }
        if ($user->role === 'student' && (int) $delivery['student_id'] === $user->id) {
            return;
        }
        if ($user->role === 'student') {
            throw new CommentException('Delivery not found.', 404);
        }
        throw new CommentException('Forbidden.', 403);
    }

    private function pagination(int $page, int $perPage): void
    {
        if ($page < 1 || $perPage < 1 || $perPage > 100) {
            throw new CommentException('Invalid pagination.', 400);
        }
    }

    private function format(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'delivery_id' => (int) $row['delivery_id'],
            'author' => [
                'id' => (int) $row['author_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'role' => $row['role'],
            ],
            'body' => $row['body'],
            'created_at' => (new \DateTimeImmutable($row['created_at'], new \DateTimeZone('UTC')))->format(DATE_ATOM),
        ];
    }
}
