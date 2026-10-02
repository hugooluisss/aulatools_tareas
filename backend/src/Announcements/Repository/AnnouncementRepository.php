<?php

declare(strict_types=1);

namespace App\Announcements\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class AnnouncementRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function list(int $schoolId, bool $activeOnly, int $offset, int $limit): array
    {
        $period = $activeOnly ? ' AND starts_on <= CURRENT_DATE AND ends_on >= CURRENT_DATE' : '';
        $sql = "FROM announcements WHERE school_id = :school_id{$period}";
        $params = [':school_id' => $schoolId, ':limit' => $limit, ':offset' => $offset];
        $data = $this->db->createCommand(<<<SQL
            SELECT id, school_id, title, body, starts_on, ends_on {$sql}
            ORDER BY starts_on DESC, id DESC LIMIT :limit OFFSET :offset
            SQL, $params)->queryAll();
        $total = (int) $this->db->createCommand(<<<SQL
            SELECT COUNT(*) {$sql}
            SQL, [':school_id' => $schoolId])->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    public function find(int $schoolId, int $id): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT id, school_id, title, body, starts_on, ends_on
            FROM announcements WHERE school_id = :school_id AND id = :id
            SQL, [':school_id' => $schoolId, ':id' => $id])->queryOne() ?: null;
    }

    public function create(int $schoolId, array $data): int
    {
        $this->db->createCommand(
            <<<'SQL'
            INSERT INTO announcements (school_id, title, body, starts_on, ends_on)
            VALUES (:school_id, :title, :body, :starts_on, :ends_on)
            SQL,
            [
                ':school_id' => $schoolId,
                ':title' => $data['title'],
                ':body' => $data['body'],
                ':starts_on' => $data['starts_on'],
                ':ends_on' => $data['ends_on'],
            ],
        )->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->createCommand(
            <<<'SQL'
            UPDATE announcements SET title = :title, body = :body, starts_on = :starts_on, ends_on = :ends_on
            WHERE id = :id
            SQL,
            [
                ':id' => $id,
                ':title' => $data['title'],
                ':body' => $data['body'],
                ':starts_on' => $data['starts_on'],
                ':ends_on' => $data['ends_on'],
            ],
        )->execute();
    }

    public function delete(int $id): void
    {
        $this->db->createCommand(<<<'SQL'
            DELETE FROM announcements WHERE id = :id
            SQL, [':id' => $id])->execute();
    }
}
