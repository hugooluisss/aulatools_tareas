<?php

declare(strict_types=1);

namespace App\Cycles\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class CycleRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function list(int $schoolId, int $offset, int $limit): array
    {
        $data = $this->db->createCommand(
            <<<'SQL'
                SELECT id, school_id, name, starts_on, ends_on, status
                FROM academic_cycles WHERE school_id = :school_id
                ORDER BY id LIMIT :limit OFFSET :offset
                SQL,
            [':school_id' => $schoolId, ':limit' => $limit, ':offset' => $offset],
        )->queryAll();
        $total = (int) $this->db->createCommand(
            <<<'SQL'
                SELECT COUNT(*) FROM academic_cycles WHERE school_id = :school_id
                SQL,
            [':school_id' => $schoolId],
        )->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    public function find(int $schoolId, int $id): ?array
    {
        return $this->db->createCommand(
            <<<'SQL'
                SELECT id, school_id, name, starts_on, ends_on, status
                FROM academic_cycles WHERE school_id = :school_id AND id = :id
                SQL,
            [':school_id' => $schoolId, ':id' => $id],
        )->queryOne() ?: null;
    }

    public function create(int $schoolId, array $data): int
    {
        $this->db->createCommand(
            <<<'SQL'
                INSERT INTO academic_cycles (school_id, name, starts_on, ends_on)
                VALUES (:school_id, :name, :starts_on, :ends_on)
                SQL,
            [
                ':school_id' => $schoolId,
                ':name' => $data['name'],
                ':starts_on' => $data['starts_on'],
                ':ends_on' => $data['ends_on'],
            ],
        )->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function update(int $schoolId, int $id, array $data): void
    {
        $this->db->createCommand(
            <<<'SQL'
                UPDATE academic_cycles SET name = :name, starts_on = :starts_on, ends_on = :ends_on
                WHERE id = :id AND school_id = :school_id
                SQL,
            [
                ':name' => $data['name'],
                ':starts_on' => $data['starts_on'],
                ':ends_on' => $data['ends_on'],
                ':id' => $id,
                ':school_id' => $schoolId,
            ],
        )->execute();
    }

    public function isOpen(int $schoolId, int $cycleId): bool
    {
        return $this->db->createCommand(
            <<<'SQL'
                SELECT id FROM academic_cycles
                WHERE id = :id AND school_id = :school_id AND status = 'active'
                SQL,
            [':id' => $cycleId, ':school_id' => $schoolId],
        )->queryOne() !== null;
    }

    public function finish(int $schoolId, int $id): void
    {
        $this->db->createCommand(
            <<<'SQL'
                UPDATE subjects SET status = 'finished' WHERE cycle_id = :id
                SQL,
            [':id' => $id],
        )->execute();
        $this->db->createCommand(
            <<<'SQL'
                UPDATE academic_cycles SET status = 'finished'
                WHERE id = :id AND school_id = :school_id
                SQL,
            [':id' => $id, ':school_id' => $schoolId],
        )->execute();
    }
}
