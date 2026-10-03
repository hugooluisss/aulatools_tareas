<?php

declare(strict_types=1);

namespace App\TaskComments\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class CommentRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function delivery(int $schoolId, int $deliveryId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT task_deliveries.id, task_deliveries.student_id, subjects.teacher_id, subjects.school_id
            FROM task_deliveries
            INNER JOIN tasks ON tasks.id = task_deliveries.task_id
            INNER JOIN subjects ON subjects.id = tasks.subject_id
            WHERE task_deliveries.id = :delivery_id AND subjects.school_id = :school_id
            SQL, [':delivery_id' => $deliveryId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function list(int $deliveryId, int $offset, int $limit): array
    {
        $data = $this->db->createCommand(<<<'SQL'
            SELECT task_comments.id, task_comments.delivery_id, task_comments.body, task_comments.created_at,
                   users.id AS author_id, users.first_name, users.last_name, users.role
            FROM task_comments
            INNER JOIN users ON users.id = task_comments.author_id
            WHERE task_comments.delivery_id = :delivery_id
            ORDER BY task_comments.created_at, task_comments.id
            LIMIT :limit OFFSET :offset
            SQL, [':delivery_id' => $deliveryId, ':limit' => $limit, ':offset' => $offset])->queryAll();
        $total = (int) $this->db->createCommand(<<<'SQL'
            SELECT COUNT(*) FROM task_comments WHERE delivery_id = :delivery_id
            SQL, [':delivery_id' => $deliveryId])->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    public function create(int $deliveryId, int $authorId, string $body): int
    {
        $this->db->createCommand(<<<'SQL'
            INSERT INTO task_comments (delivery_id, author_id, body, created_at)
            VALUES (:delivery_id, :author_id, :body, UTC_TIMESTAMP())
            SQL, [':delivery_id' => $deliveryId, ':author_id' => $authorId, ':body' => $body])->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function find(int $commentId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT task_comments.id, task_comments.delivery_id, task_comments.body, task_comments.created_at,
                   users.id AS author_id, users.first_name, users.last_name, users.role
            FROM task_comments
            INNER JOIN users ON users.id = task_comments.author_id
            WHERE task_comments.id = :id
            SQL, [':id' => $commentId])->queryOne() ?: null;
    }
}
