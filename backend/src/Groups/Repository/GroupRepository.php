<?php

declare(strict_types=1);

namespace App\Groups\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

final class GroupRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function list(int $schoolId, int $offset, int $limit): array
    {
        $rows = $this->db->createCommand(<<<'SQL'
            SELECT id, school_id, name
            FROM `groups`
            WHERE school_id = :school_id
            ORDER BY id LIMIT :limit OFFSET :offset
            SQL, [':school_id' => $schoolId, ':limit' => $limit, ':offset' => $offset])->queryAll();
        $total = (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM `groups` WHERE school_id = :school_id',
            [':school_id' => $schoolId],
        )->queryScalar();
        foreach ($rows as &$row) {
            $row['subjects'] = $this->subjects($schoolId, (int) $row['id'], 0, 1000)['data'];
        }
        return ['data' => $rows, 'total' => $total];
    }

    public function find(int $schoolId, int $id): ?array
    {
        return $this->db->createCommand(
            'SELECT id, school_id, name FROM `groups` WHERE id = :id AND school_id = :school_id',
            [':id' => $id, ':school_id' => $schoolId],
        )->queryOne();
    }

    public function subjectIdsBelongToSchool(int $schoolId, array $ids): bool
    {
        if ($ids === []) {
            return true;
        }
        $holders = [];
        $params = [':school_id' => $schoolId];
        foreach (array_values(array_unique($ids)) as $index => $id) {
            $holders[] = ':id' . $index;
            $params[':id' . $index] = $id;
        }
        $count = (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM subjects INNER JOIN academic_cycles ON academic_cycles.id = subjects.cycle_id '
            . 'WHERE academic_cycles.school_id = :school_id AND subjects.id IN ('
            . implode(', ', $holders) . ')',
            $params,
        )->queryScalar();
        return $count === count(array_unique($ids));
    }

    public function create(int $schoolId, string $name): int
    {
        $this->db->createCommand(
            'INSERT INTO `groups` (school_id, name) VALUES (:school_id, :name)',
            [':school_id' => $schoolId, ':name' => $name],
        )->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function replaceSubjects(int $groupId, array $subjectIds): void
    {
        $this->db->createCommand(
            'DELETE FROM group_subjects WHERE group_id = :group_id',
            [':group_id' => $groupId],
        )->execute();
        foreach (array_unique($subjectIds) as $subjectId) {
            $this->db->createCommand(
                'INSERT INTO group_subjects (group_id, subject_id) VALUES (:group_id, :subject_id)',
                [':group_id' => $groupId, ':subject_id' => $subjectId],
            )->execute();
        }
    }

    public function update(int $id, string $name): void
    {
        $this->db->createCommand(
            'UPDATE `groups` SET name = :name WHERE id = :id',
            [':name' => $name, ':id' => $id],
        )->execute();
    }

    public function delete(int $id): void
    {
        $this->db->createCommand('DELETE FROM `groups` WHERE id = :id', [':id' => $id])->execute();
    }

    public function subjects(int $schoolId, int $groupId, int $offset, int $limit): array
    {
        $rows = $this->db->createCommand(
            <<<'SQL'
            SELECT subjects.id, subjects.name
            FROM group_subjects
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            INNER JOIN academic_cycles ON academic_cycles.id = subjects.cycle_id
            WHERE group_subjects.group_id = :group_id AND academic_cycles.school_id = :school_id
            ORDER BY subjects.id LIMIT :limit OFFSET :offset
            SQL,
            [':group_id' => $groupId, ':school_id' => $schoolId, ':limit' => $limit, ':offset' => $offset],
        )->queryAll();
        $total = (int) $this->db->createCommand(<<<'SQL'
            SELECT COUNT(*)
            FROM group_subjects
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            INNER JOIN academic_cycles ON academic_cycles.id = subjects.cycle_id
            WHERE group_subjects.group_id = :group_id AND academic_cycles.school_id = :school_id
            SQL, [':group_id' => $groupId, ':school_id' => $schoolId])->queryScalar();
        return ['data' => $rows, 'total' => $total];
    }
}
