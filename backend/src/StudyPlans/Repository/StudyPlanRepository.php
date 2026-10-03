<?php

declare(strict_types=1);

namespace App\StudyPlans\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class StudyPlanRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function list(int $schoolId): array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT study_plans.id, study_plans.school_id, study_plans.code, study_plans.name, study_plans.status,
                   COUNT(subjects.id) AS subjects_count
            FROM study_plans LEFT JOIN subjects ON subjects.plan_id = study_plans.id
            WHERE study_plans.school_id = :school_id
            GROUP BY study_plans.id, study_plans.school_id, study_plans.code, study_plans.name, study_plans.status
            ORDER BY study_plans.id
            SQL, [':school_id' => $schoolId])->queryAll();
    }

    public function find(int $schoolId, int $id): ?array
    {
        return $this->db->createCommand('SELECT id, school_id, code, name, status, (SELECT COUNT(*) FROM subjects WHERE plan_id = study_plans.id) AS subjects_count FROM study_plans WHERE id = :id AND school_id = :school_id', [':id' => $id, ':school_id' => $schoolId])->queryOne();
    }

    public function codeExists(int $schoolId, string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM study_plans WHERE school_id = :school_id AND code = :code';
        $params = [':school_id' => $schoolId, ':code' => $code];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params[':except_id'] = $exceptId;
        }
        return $this->db->createCommand($sql, $params)->queryOne() !== null;
    }

    public function create(int $schoolId, string $code, string $name): int
    {
        $this->db->createCommand("INSERT INTO study_plans (school_id, code, name, status) VALUES (:school_id, :code, :name, 'active')", [':school_id' => $schoolId, ':code' => $code, ':name' => $name])->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function update(int $schoolId, int $id, string $code, string $name, string $status): void
    {
        $this->db->createCommand('UPDATE study_plans SET code = :code, name = :name, status = :status WHERE id = :id AND school_id = :school_id', [':id' => $id, ':school_id' => $schoolId, ':code' => $code, ':name' => $name, ':status' => $status])->execute();
    }

    public function delete(int $schoolId, int $id): void
    {
        $this->db->createCommand('DELETE FROM study_plans WHERE id = :id AND school_id = :school_id', [':id' => $id, ':school_id' => $schoolId])->execute();
    }
}
