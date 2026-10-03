<?php

declare(strict_types=1);

namespace App\Groups\Repository;

use App\Enrollments\Service\TaskDeliveryEnrollmentHook;
use Yiisoft\Db\Connection\ConnectionInterface;

final class GroupRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function list(int $schoolId, int $offset, int $limit): array
    {
        $rows = $this->db->createCommand(<<<'SQL'
            SELECT `groups`.id, `groups`.school_id, `groups`.cycle_id, academic_cycles.name AS cycle_name, `groups`.name, `groups`.teacher_id
            FROM `groups`
            INNER JOIN academic_cycles ON academic_cycles.id = `groups`.cycle_id
            WHERE `groups`.school_id = :school_id
            ORDER BY `groups`.id LIMIT :limit OFFSET :offset
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
            'SELECT `groups`.id, `groups`.school_id, `groups`.cycle_id, academic_cycles.name AS cycle_name, `groups`.name, `groups`.teacher_id '
            . 'FROM `groups` INNER JOIN academic_cycles ON academic_cycles.id = `groups`.cycle_id '
            . 'WHERE `groups`.id = :id AND `groups`.school_id = :school_id',
            [':id' => $id, ':school_id' => $schoolId],
        )->queryOne();
    }

    public function cycleBelongsToSchool(int $schoolId, int $cycleId): bool
    {
        return (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM academic_cycles WHERE id = :cycle_id AND school_id = :school_id',
            [':cycle_id' => $cycleId, ':school_id' => $schoolId],
        )->queryScalar() === 1;
    }

    public function subjectIdsActiveInSchool(int $schoolId, array $ids): bool
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
            "SELECT COUNT(*) FROM subjects WHERE subjects.school_id = :school_id AND subjects.status = 'active' AND subjects.id IN ("
            . implode(', ', $holders) . ')',
            $params,
        )->queryScalar();
        return $count === count(array_unique($ids));
    }

    public function teacherBelongsToSchool(int $schoolId, int $teacherId): bool
    {
        return $this->db->createCommand("SELECT id FROM users WHERE id = :id AND school_id = :school_id AND role = 'teacher'", [':id' => $teacherId, ':school_id' => $schoolId])->queryOne() !== null;
    }

    public function create(int $schoolId, int $cycleId, string $name, ?int $teacherId): int
    {
        $this->db->createCommand(
            'INSERT INTO `groups` (school_id, cycle_id, name, teacher_id) VALUES (:school_id, :cycle_id, :name, :teacher_id)',
            [':school_id' => $schoolId, ':cycle_id' => $cycleId, ':name' => $name, ':teacher_id' => $teacherId],
        )->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function replaceSubjects(int $groupId, array $subjectIds, ?int $cycleId = null, ?TaskDeliveryEnrollmentHook $deliveryHook = null): void
    {
        $oldIds = array_map('intval', array_column($this->db->createCommand('SELECT subject_id FROM group_subjects WHERE group_id = :group_id', [':group_id' => $groupId])->queryAll(), 'subject_id'));
        $newIds = array_values(array_unique($subjectIds));
        $added = array_values(array_diff($newIds, $oldIds));
        $this->db->createCommand('DELETE FROM group_subjects WHERE group_id = :group_id', [':group_id' => $groupId])->execute();
        foreach ($newIds as $subjectId) {
            $this->db->createCommand(
                'INSERT INTO group_subjects (group_id, subject_id) VALUES (:group_id, :subject_id)',
                [':group_id' => $groupId, ':subject_id' => $subjectId],
            )->execute();
        }
        if ($added !== [] && $cycleId !== null) {
            $holders = [];
            $params = [':group_id' => $groupId, ':cycle_id' => $cycleId];
            foreach ($added as $index => $subjectId) {
                $holders[] = ':subject' . $index;
                $params[':subject' . $index] = $subjectId;
            }
            $this->db->createCommand(<<<'SQL'
                INSERT IGNORE INTO enrollment_subject_bindings (enrollment_id, subject_id, teacher_id)
                SELECT enrollments.id, group_subjects.subject_id, COALESCE(`groups`.teacher_id, subjects.teacher_id)
                FROM enrollments
                INNER JOIN group_subjects ON group_subjects.group_id = enrollments.group_id
                INNER JOIN `groups` ON `groups`.id = enrollments.group_id
                INNER JOIN subjects ON subjects.id = group_subjects.subject_id
                WHERE enrollments.group_id = :group_id AND enrollments.cycle_id = :cycle_id
                  AND group_subjects.subject_id IN (
            SQL . implode(', ', $holders) . ')', $params)->execute();
            $deliveryHook?->onGroupSubjectsAdded($groupId, $cycleId, $added);
        }
    }

    public function update(int $id, int $cycleId, string $name, ?int $teacherId): void
    {
        $this->db->createCommand(
            'UPDATE `groups` SET cycle_id = :cycle_id, name = :name, teacher_id = :teacher_id WHERE id = :id',
            [':cycle_id' => $cycleId, ':name' => $name, ':teacher_id' => $teacherId, ':id' => $id],
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
            WHERE group_subjects.group_id = :group_id AND subjects.school_id = :school_id
            ORDER BY subjects.id LIMIT :limit OFFSET :offset
            SQL,
            [':group_id' => $groupId, ':school_id' => $schoolId, ':limit' => $limit, ':offset' => $offset],
        )->queryAll();
        $total = (int) $this->db->createCommand(<<<'SQL'
            SELECT COUNT(*)
            FROM group_subjects
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            WHERE group_subjects.group_id = :group_id AND subjects.school_id = :school_id
            SQL, [':group_id' => $groupId, ':school_id' => $schoolId])->queryScalar();
        return ['data' => $rows, 'total' => $total];
    }
}
