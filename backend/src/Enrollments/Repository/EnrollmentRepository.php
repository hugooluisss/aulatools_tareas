<?php

declare(strict_types=1);

namespace App\Enrollments\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class EnrollmentRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function studentExists(int $schoolId, int $studentId): bool
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT users.id FROM users
            INNER JOIN students ON students.user_id = users.id
            WHERE users.id = :student_id AND users.school_id = :school_id AND users.role = 'student'
            SQL, [':student_id' => $studentId, ':school_id' => $schoolId])->queryOne() !== null;
    }

    public function cycle(int $schoolId, int $cycleId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT id, name, status FROM academic_cycles
            WHERE id = :cycle_id AND school_id = :school_id
            SQL, [':cycle_id' => $cycleId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function group(int $schoolId, int $groupId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT groups_table.id, groups_table.cycle_id, academic_cycles.school_id
            FROM `groups` AS groups_table
            INNER JOIN academic_cycles ON academic_cycles.id = groups_table.cycle_id
            WHERE groups_table.id = :group_id AND groups_table.school_id = :school_id
            SQL, [':group_id' => $groupId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function find(int $schoolId, int $id): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT enrollments.id, enrollments.student_id, enrollments.cycle_id,
                   academic_cycles.name AS cycle_name, enrollments.group_id,
                   groups_table.name AS group_name, enrollments.created_at
            FROM enrollments
            INNER JOIN academic_cycles ON academic_cycles.id = enrollments.cycle_id
            INNER JOIN `groups` AS groups_table ON groups_table.id = enrollments.group_id
            WHERE enrollments.id = :id AND academic_cycles.school_id = :school_id
            SQL, [':id' => $id, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function findMany(int $schoolId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        [$in, $params] = $this->inParams($ids, 'enrollment');
        $params[':school_id'] = $schoolId;
        return $this->db->createCommand(<<<SQL
            SELECT enrollments.id, enrollments.student_id, enrollments.cycle_id,
                   academic_cycles.name AS cycle_name, enrollments.group_id,
                   groups_table.name AS group_name, enrollments.created_at
            FROM enrollments
            INNER JOIN academic_cycles ON academic_cycles.id = enrollments.cycle_id
            INNER JOIN `groups` AS groups_table ON groups_table.id = enrollments.group_id
            WHERE academic_cycles.school_id = :school_id AND enrollments.id IN ({$in})
            ORDER BY enrollments.id
            SQL, $params)->queryAll();
    }

    public function list(int $schoolId, ?int $cycleId, ?int $studentId): array
    {
        $where = ['academic_cycles.school_id = :school_id'];
        $params = [':school_id' => $schoolId];
        if ($cycleId !== null) {
            $params[':cycle_id'] = $cycleId;
        }
        if ($studentId !== null) {
            $params[':student_id'] = $studentId;
        }
        return $this->db->createCommand(<<<SQL
            SELECT enrollments.id, enrollments.student_id,
                   students.first_name AS student_first_name, students.last_name AS student_last_name,
                   enrollments.cycle_id, academic_cycles.name AS cycle_name,
                   enrollments.group_id, groups_table.name AS group_name, enrollments.created_at
            FROM enrollments
            INNER JOIN academic_cycles ON academic_cycles.id = enrollments.cycle_id
            INNER JOIN `groups` AS groups_table ON groups_table.id = enrollments.group_id
            INNER JOIN users AS students ON students.id = enrollments.student_id
            WHERE {$where[0]}
            SQL . ($cycleId !== null ? ' AND enrollments.cycle_id = :cycle_id' : '')
                . ($studentId !== null ? ' AND enrollments.student_id = :student_id' : '')
                . ' ORDER BY enrollments.created_at DESC, enrollments.id DESC', $params)->queryAll();
    }

    public function existsForCycle(int $studentId, int $cycleId): bool
    {
        return $this->db->createCommand(
            'SELECT id FROM enrollments WHERE student_id = :student_id AND cycle_id = :cycle_id',
            [':student_id' => $studentId, ':cycle_id' => $cycleId],
        )->queryOne() !== null;
    }

    public function aspirants(int $schoolId): array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT users.id AS student_id, users.first_name, users.last_name
            FROM users
            INNER JOIN students ON students.user_id = users.id
            WHERE users.school_id = :school_id AND users.role = 'student' AND students.status = 'active'
              AND NOT EXISTS (SELECT 1 FROM enrollments WHERE enrollments.student_id = users.id)
            ORDER BY users.last_name, users.first_name, users.id
            SQL, [':school_id' => $schoolId])->queryAll();
    }

    public function reenrollable(int $schoolId): array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT candidates.student_id, candidates.first_name, candidates.last_name,
                   candidates.cycle_id, candidates.cycle_name, candidates.group_id, candidates.group_name
            FROM (
                SELECT users.id AS student_id, users.first_name, users.last_name,
                       enrollments.cycle_id, academic_cycles.name AS cycle_name,
                       enrollments.group_id, groups_table.name AS group_name,
                       ROW_NUMBER() OVER (
                           PARTITION BY users.id ORDER BY enrollments.created_at DESC, enrollments.id DESC
                       ) AS row_num
                FROM users
                INNER JOIN students ON students.user_id = users.id
                INNER JOIN enrollments ON enrollments.student_id = users.id
                INNER JOIN academic_cycles ON academic_cycles.id = enrollments.cycle_id
                INNER JOIN `groups` AS groups_table ON groups_table.id = enrollments.group_id
                WHERE users.school_id = :school_id AND users.role = 'student' AND students.status = 'active'
            ) AS candidates
            WHERE candidates.row_num = 1
              AND EXISTS (
                  SELECT 1 FROM academic_cycles finished_cycles
                  WHERE finished_cycles.id = candidates.cycle_id AND finished_cycles.status = 'finished'
              )
              AND NOT EXISTS (
                  SELECT 1 FROM enrollments
                  INNER JOIN academic_cycles active_cycles ON active_cycles.id = enrollments.cycle_id
                  WHERE enrollments.student_id = candidates.student_id AND active_cycles.status = 'active'
              )
            ORDER BY candidates.last_name, candidates.first_name, candidates.student_id
            SQL, [':school_id' => $schoolId])->queryAll();
    }

    public function studentsByIds(int $schoolId, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }
        [$in, $params] = $this->inParams($studentIds, 'student');
        $params[':school_id'] = $schoolId;
        return $this->db->createCommand(<<<SQL
            SELECT users.id AS student_id FROM users
            INNER JOIN students ON students.user_id = users.id
            WHERE users.school_id = :school_id AND users.role = 'student' AND students.status = 'active'
              AND users.id IN ({$in})
            SQL, $params)->queryAll();
    }

    public function eligibleIds(int $schoolId, array $studentIds, string $type): array
    {
        if ($studentIds === []) {
            return [];
        }
        [$in, $params] = $this->inParams($studentIds, 'eligible');
        $params[':school_id'] = $schoolId;
        if ($type === 'enrollment') {
            $condition = 'NOT EXISTS (SELECT 1 FROM enrollments WHERE enrollments.student_id = users.id)';
        } else {
            $condition = <<<'SQL'
                EXISTS (
                    SELECT 1 FROM enrollments latest
                    WHERE latest.student_id = users.id
                      AND EXISTS (
                          SELECT 1 FROM academic_cycles finished_cycles
                          WHERE finished_cycles.id = latest.cycle_id AND finished_cycles.status = 'finished'
                      )
                      AND NOT EXISTS (
                          SELECT 1 FROM enrollments newer
                          WHERE newer.student_id = latest.student_id
                            AND (newer.created_at > latest.created_at
                              OR (newer.created_at = latest.created_at AND newer.id > latest.id))
                      )
                      AND NOT EXISTS (
                          SELECT 1 FROM enrollments
                          INNER JOIN academic_cycles active_cycles ON active_cycles.id = enrollments.cycle_id
                          WHERE enrollments.student_id = users.id AND active_cycles.status = 'active'
                      )
                )
                SQL;
        }
        return $this->db->createCommand(<<<SQL
            SELECT users.id AS student_id FROM users
            INNER JOIN students ON students.user_id = users.id
            WHERE users.school_id = :school_id AND users.role = 'student' AND students.status = 'active'
              AND users.id IN ({$in}) AND {$condition}
            SQL, $params)->queryAll();
    }

    public function destinationConflicts(array $studentIds, int $cycleId): array
    {
        if ($studentIds === []) {
            return [];
        }
        [$in, $params] = $this->inParams($studentIds, 'destination');
        $params[':cycle_id'] = $cycleId;
        return $this->db->createCommand(<<<SQL
            SELECT student_id FROM enrollments
            WHERE cycle_id = :cycle_id AND student_id IN ({$in})
            SQL, $params)->queryAll();
    }

    public function previousCycleConflicts(array $studentIds, int $cycleId): array
    {
        if ($studentIds === []) {
            return [];
        }
        [$in, $params] = $this->inParams($studentIds, 'previous');
        $params[':cycle_id'] = $cycleId;
        return $this->db->createCommand(<<<SQL
            SELECT latest.student_id FROM enrollments latest
            WHERE latest.cycle_id = :cycle_id AND latest.student_id IN ({$in})
              AND NOT EXISTS (
                  SELECT 1 FROM enrollments newer
                  WHERE newer.student_id = latest.student_id
                    AND (newer.created_at > latest.created_at
                      OR (newer.created_at = latest.created_at AND newer.id > latest.id))
              )
            SQL, $params)->queryAll();
    }

    private function inParams(array $ids, string $prefix): array
    {
        $params = [];
        $names = [];
        foreach (array_values($ids) as $index => $id) {
            $name = ':' . $prefix . $index;
            $names[] = $name;
            $params[$name] = $id;
        }
        return [implode(', ', $names), $params];
    }

    public function create(int $studentId, int $cycleId, int $groupId): int
    {
        $this->db->createCommand(<<<'SQL'
            INSERT INTO enrollments (student_id, cycle_id, group_id)
            VALUES (:student_id, :cycle_id, :group_id)
            SQL, [':student_id' => $studentId, ':cycle_id' => $cycleId, ':group_id' => $groupId])->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function updateGroup(int $id, int $groupId): void
    {
        $this->db->createCommand(
            'UPDATE enrollments SET group_id = :group_id WHERE id = :id',
            [':group_id' => $groupId, ':id' => $id],
        )->execute();
    }

    public function delete(int $id): void
    {
        $this->db->createCommand('DELETE FROM enrollments WHERE id = :id', [':id' => $id])->execute();
    }

    public function groupSubjects(int $groupId, int $schoolId): array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id FROM group_subjects
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            INNER JOIN `groups` ON `groups`.id = group_subjects.group_id
            WHERE group_subjects.group_id = :group_id AND `groups`.school_id = :school_id
            ORDER BY subjects.id
            SQL, [':group_id' => $groupId, ':school_id' => $schoolId])->queryAll();
    }

    public function syncGroupSubjectsForStudents(int $schoolId, int $groupId, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }
        $group = $this->group($schoolId, $groupId);
        if ($group === null) {
            return [];
        }
        [$in, $params] = $this->inParams($studentIds, 'student');
        $params[':cycle_id'] = $group['cycle_id'];
        $params[':group_id'] = $groupId;
        $this->db->createCommand(<<<SQL
            INSERT IGNORE INTO enrollment_subject_bindings (enrollment_id, subject_id, teacher_id)
            SELECT enrollments.id, group_subjects.subject_id, COALESCE(groups_table.teacher_id, subjects.teacher_id)
            FROM enrollments
            INNER JOIN group_subjects ON group_subjects.group_id = enrollments.group_id
            INNER JOIN `groups` AS groups_table ON groups_table.id = enrollments.group_id
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            WHERE enrollments.student_id IN ({$in}) AND enrollments.cycle_id = :cycle_id
              AND enrollments.group_id = :group_id
            SQL, $params)->execute();
        return array_map('intval', array_column($this->groupSubjects($groupId, $schoolId), 'id'));
    }

    public function syncGroupSubjects(int $schoolId, int $studentId, int $groupId): array
    {
        $group = $this->group($schoolId, $groupId);
        if ($group === null) {
            return [];
        }
        $this->db->createCommand(<<<'SQL'
            INSERT IGNORE INTO enrollment_subject_bindings (enrollment_id, subject_id, teacher_id)
            SELECT enrollments.id, group_subjects.subject_id, COALESCE(groups_table.teacher_id, subjects.teacher_id)
            FROM enrollments
            INNER JOIN group_subjects ON group_subjects.group_id = enrollments.group_id
            INNER JOIN `groups` AS groups_table ON groups_table.id = enrollments.group_id
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            WHERE enrollments.student_id = :student_id AND enrollments.cycle_id = :cycle_id
              AND enrollments.group_id = :group_id
            SQL, [':student_id' => $studentId, ':cycle_id' => $group['cycle_id'], ':group_id' => $groupId])->execute();
        return array_map('intval', array_column($this->groupSubjects($groupId, $schoolId), 'id'));
    }

    public function removeGroupBindings(int $enrollmentId, int $groupId): void
    {
        $this->db->createCommand(<<<'SQL'
            DELETE bindings FROM enrollment_subject_bindings bindings
            INNER JOIN group_subjects ON group_subjects.subject_id = bindings.subject_id
            WHERE bindings.enrollment_id = :enrollment_id AND group_subjects.group_id = :group_id
            SQL, [':enrollment_id' => $enrollmentId, ':group_id' => $groupId])->execute();
    }

}
