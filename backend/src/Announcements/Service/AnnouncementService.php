<?php

declare(strict_types=1);

namespace App\Announcements\Service;

use App\Auth\CurrentUser;
use App\Announcements\Repository\AnnouncementRepository;

final class AnnouncementService
{
    public function __construct(private AnnouncementRepository $repository)
    {
    }

    public function list(CurrentUser $user, int $page, int $perPage): array
    {
        $this->admin($user);
        return $this->listing($user, false, $page, $perPage);
    }

    public function active(CurrentUser $user, int $page, int $perPage): array
    {
        if (!in_array($user->role, ['admin', 'teacher', 'student'], true)) {
            throw new AnnouncementException('Forbidden.', 403);
        }
        return $this->listing($user, true, $page, $perPage);
    }

    public function find(CurrentUser $user, int $id): array
    {
        $this->admin($user);
        return $this->repository->find($user->schoolId, $id)
            ?? throw new AnnouncementException('Announcement not found.', 404);
    }

    public function create(CurrentUser $user, array $data): array
    {
        $this->admin($user);
        $id = $this->repository->create($user->schoolId, $this->validate($data));
        return $this->find($user, $id);
    }

    public function update(CurrentUser $user, int $id, array $data): array
    {
        $this->admin($user);
        $this->find($user, $id);
        $this->repository->update($id, $this->validate($data));
        return $this->find($user, $id);
    }

    public function delete(CurrentUser $user, int $id): void
    {
        $this->admin($user);
        $this->find($user, $id);
        $this->repository->delete($id);
    }

    private function listing(CurrentUser $user, bool $activeOnly, int $page, int $perPage): array
    {
        $this->pagination($page, $perPage);
        $result = $this->repository->list($user->schoolId, $activeOnly, ($page - 1) * $perPage, $perPage);
        return \App\Shared\Paginator::build($result['data'], $result['total'], $page, $perPage);
    }

    private function validate(array $data): array
    {
        foreach (['title', 'body', 'starts_on', 'ends_on'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                throw new AnnouncementException("Invalid {$field}.", 400);
            }
        }
        foreach (['starts_on', 'ends_on'] as $field) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $data[$field]);
            if ($date === false || $date->format('Y-m-d') !== $data[$field]) {
                throw new AnnouncementException("Invalid {$field}.", 400);
            }
        }
        if ($data['ends_on'] < $data['starts_on']) {
            throw new AnnouncementException('End date must not precede start date.', 400);
        }
        return [
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
        ];
    }

    private function pagination(int $page, int $perPage): void
    {
        if ($page < 1 || $perPage < 1 || $perPage > 100) {
            throw new AnnouncementException('Invalid pagination.', 400);
        }
    }

    private function admin(CurrentUser $user): void
    {
        if ($user->role !== 'admin') {
            throw new AnnouncementException('Forbidden.', 403);
        }
    }
}
