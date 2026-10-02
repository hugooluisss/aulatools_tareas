<?php

declare(strict_types=1);

namespace App\Subjects\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class SubjectRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function list(int $schoolId, string $role, int $userId, array $filters, int $offset, int $limit): array
    {
        $where = ['academic_cycles.school_id = :school_id'];
        $params = [':school_id' => $schoolId, ':limit' => $limit, ':offset' => $offset];
        if ($role === 'teacher') {
            $where[] = 'subjects.teacher_id = :user_id';
            $params[':user_id'] = $userId;
        } elseif ($role === 'student') {
            $where[] = <<<'SQL'
                EXISTS (
                    SELECT 1 FROM enrollments
                    WHERE enrollments.subject_id = subjects.id
                        AND enrollments.student_id = :user_id
                )
                SQL;
            $params[':user_id'] = $userId;
        }
        foreach (['cycle_id', 'status'] as $field) {
            if (isset($filters[$field])) {
                $where[] = "subjects.$field = :$field";
                $params[":$field"] = $filters[$field];
            }
        }
        $clause = implode(' AND ', $where);
        $rows = $this->db->createCommand(
            <<<SQL
                SELECT subjects.id, subjects.cycle_id, subjects.teacher_id, subjects.name, subjects.status
                FROM subjects
                INNER JOIN academic_cycles ON academic_cycles.id = subjects.cycle_id
                WHERE $clause
                ORDER BY subjects.id LIMIT :limit OFFSET :offset
                SQL,
            $params,
        )->queryAll();
        $total = (int) $this->db->createCommand(
            <<<SQL
                SELECT COUNT(*)
                FROM subjects
                INNER JOIN academic_cycles ON academic_cycles.id = subjects.cycle_id
                WHERE $clause
                SQL,
            array_diff_key($params, [':limit' => true, ':offset' => true]),
        )->queryScalar();
        return ['data' => $rows, 'total' => $total];
    }

    public function find(int $schoolId, int $subjectId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id, subjects.cycle_id, subjects.teacher_id, subjects.name, subjects.status
            FROM subjects
            INNER JOIN academic_cycles ON academic_cycles.id = subjects.cycle_id
            WHERE subjects.id = :id AND academic_cycles.school_id = :school_id
            SQL, [':id' => $subjectId, ':school_id' => $schoolId])->queryOne();
    }

    public function isEnrolled(int $subjectId, int $studentId): bool
    {
        return $this->db->createCommand(
            'SELECT student_id FROM enrollments WHERE subject_id = :subject_id AND student_id = :student_id',
            [':subject_id' => $subjectId, ':student_id' => $studentId],
        )->queryOne() !== null;
    }

    public function cycleIsActive(int $schoolId, int $cycleId): bool
    {
        return $this->db->createCommand(
            "SELECT id FROM academic_cycles WHERE id = :id AND school_id = :school_id AND status = 'active'",
            [':id' => $cycleId, ':school_id' => $schoolId],
        )->queryOne() !== null;
    }

    public function teacherBelongsToSchool(int $schoolId, int $teacherId): bool
    {
        return $this->db->createCommand(
            "SELECT id FROM users WHERE id = :id AND school_id = :school_id AND role = 'teacher'",
            [':id' => $teacherId, ':school_id' => $schoolId],
        )->queryOne() !== null;
    }

    public function create(int $cycleId, int $teacherId, string $name): int
    {
        $this->db->createCommand(<<<'SQL'
            INSERT INTO subjects (cycle_id, teacher_id, name, status)
            VALUES (:cycle_id, :teacher_id, :name, 'in_progress')
            SQL, [':cycle_id' => $cycleId, ':teacher_id' => $teacherId, ':name' => $name])->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function update(int $id, int $cycleId, int $teacherId, string $name, string $status): void
    {
        $this->db->createCommand(
            <<<'SQL'
            UPDATE subjects
            SET cycle_id = :cycle_id, teacher_id = :teacher_id, name = :name, status = :status
            WHERE id = :id
            SQL,
            [
                ':id' => $id,
                ':cycle_id' => $cycleId,
                ':teacher_id' => $teacherId,
                ':name' => $name,
                ':status' => $status,
            ],
        )->execute();
    }

    public function delete(int $id): void
    {
        $this->db->createCommand('DELETE FROM subjects WHERE id = :id', [':id' => $id])->execute();
    }

    public function students(int $subjectId, int $offset, int $limit): array
    {
        $rows = $this->db->createCommand(<<<'SQL'
            SELECT users.id, users.school_id, users.role, users.email, users.first_name, users.last_name,
                   students.enrollment_number, students.birth_date, students.status
            FROM enrollments
            INNER JOIN users ON users.id = enrollments.student_id
            INNER JOIN students ON students.user_id = users.id
            WHERE enrollments.subject_id = :subject_id
            ORDER BY users.id LIMIT :limit OFFSET :offset
            SQL, [':subject_id' => $subjectId, ':limit' => $limit, ':offset' => $offset])->queryAll();
        $total = (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM enrollments WHERE subject_id = :subject_id',
            [':subject_id' => $subjectId],
        )->queryScalar();
        return ['data' => $rows, 'total' => $total];
    }
}
