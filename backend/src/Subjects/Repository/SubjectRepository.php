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
        $where = ['subjects.school_id = :school_id'];
        $params = [':school_id' => $schoolId, ':limit' => $limit, ':offset' => $offset];
        if ($role === 'teacher') {
            $where[] = 'subjects.teacher_id = :user_id';
            $params[':user_id'] = $userId;
        } elseif ($role === 'student') {
            $where[] = 'EXISTS (SELECT 1 FROM enrollment_subject_bindings b INNER JOIN enrollments e ON e.id = b.enrollment_id WHERE b.subject_id = subjects.id AND e.student_id = :user_id)';
            $params[':user_id'] = $userId;
        }
        if (isset($filters['status'])) {
            if (!in_array($filters['status'], ['active', 'inactive'], true)) {
                throw new \InvalidArgumentException('Invalid status.');
            }
            $where[] = 'subjects.status = :status';
            $params[':status'] = $filters['status'];
        }
        $clause = implode(' AND ', $where);
        $rows = $this->db->createCommand(<<<SQL
            SELECT subjects.id, subjects.school_id, subjects.code, subjects.plan_id, study_plans.name AS plan_name,
                   subjects.teacher_id, subjects.name, subjects.status, COUNT(DISTINCT e.student_id) AS students_count
            FROM subjects
            LEFT JOIN study_plans ON study_plans.id = subjects.plan_id
            LEFT JOIN enrollment_subject_bindings b ON b.subject_id = subjects.id
            LEFT JOIN enrollments e ON e.id = b.enrollment_id
            WHERE $clause
            GROUP BY subjects.id, subjects.school_id, subjects.code, subjects.plan_id, study_plans.name,
                     subjects.teacher_id, subjects.name, subjects.status
            ORDER BY subjects.id LIMIT :limit OFFSET :offset
            SQL, $params)->queryAll();
        $total = (int) $this->db->createCommand("SELECT COUNT(*) FROM subjects WHERE $clause", array_diff_key($params, [':limit' => true, ':offset' => true]))->queryScalar();
        return ['data' => $rows, 'total' => $total];
    }

    public function find(int $schoolId, int $subjectId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id, subjects.school_id, subjects.code, subjects.plan_id, study_plans.name AS plan_name,
                   subjects.teacher_id, subjects.name, subjects.status,
                   (SELECT COUNT(DISTINCT e.student_id) FROM enrollment_subject_bindings b INNER JOIN enrollments e ON e.id = b.enrollment_id WHERE b.subject_id = subjects.id) AS students_count
            FROM subjects LEFT JOIN study_plans ON study_plans.id = subjects.plan_id
            WHERE subjects.id = :id AND subjects.school_id = :school_id
            SQL, [':id' => $subjectId, ':school_id' => $schoolId])->queryOne();
    }

    public function isEnrolled(int $subjectId, int $studentId): bool
    {
        return $this->db->createCommand('SELECT e.student_id FROM enrollment_subject_bindings b INNER JOIN enrollments e ON e.id = b.enrollment_id WHERE b.subject_id = :subject_id AND e.student_id = :student_id', [':subject_id' => $subjectId, ':student_id' => $studentId])->queryOne() !== null;
    }

    public function studentCount(int $subjectId): int
    {
        return (int) $this->db->createCommand('SELECT COUNT(DISTINCT e.student_id) FROM enrollment_subject_bindings b INNER JOIN enrollments e ON e.id = b.enrollment_id WHERE b.subject_id = :subject_id', [':subject_id' => $subjectId])->queryScalar();
    }

    public function teacherBelongsToSchool(int $schoolId, int $teacherId): bool
    {
        return $this->db->createCommand("SELECT id FROM users WHERE id = :id AND school_id = :school_id AND role = 'teacher'", [':id' => $teacherId, ':school_id' => $schoolId])->queryOne() !== null;
    }

    public function activePlanBelongsToSchool(int $schoolId, int $planId): bool
    {
        return $this->db->createCommand("SELECT id FROM study_plans WHERE id = :id AND school_id = :school_id AND status = 'active'", [':id' => $planId, ':school_id' => $schoolId])->queryOne() !== null;
    }

    public function codeExists(int $schoolId, string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM subjects WHERE school_id = :school_id AND code = :code';
        $params = [':school_id' => $schoolId, ':code' => $code];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params[':except_id'] = $exceptId;
        }
        return $this->db->createCommand($sql, $params)->queryOne() !== null;
    }

    public function create(int $schoolId, string $code, int $teacherId, string $name, ?int $planId): int
    {
        $this->db->createCommand("INSERT INTO subjects (school_id, code, plan_id, teacher_id, name, status) VALUES (:school_id, :code, :plan_id, :teacher_id, :name, 'active')", [':school_id' => $schoolId, ':code' => $code, ':plan_id' => $planId, ':teacher_id' => $teacherId, ':name' => $name])->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function update(int $schoolId, int $id, string $code, int $teacherId, string $name, string $status, ?int $planId): void
    {
        $this->db->createCommand('UPDATE subjects SET code = :code, plan_id = :plan_id, teacher_id = :teacher_id, name = :name, status = :status WHERE id = :id AND school_id = :school_id', [':id' => $id, ':school_id' => $schoolId, ':code' => $code, ':plan_id' => $planId, ':teacher_id' => $teacherId, ':name' => $name, ':status' => $status])->execute();
    }

    public function delete(int $id): void
    {
        $this->db->createCommand('DELETE FROM subjects WHERE id = :id', [':id' => $id])->execute();
    }

    public function students(int $subjectId, int $offset, int $limit, ?int $cycleId = null): array
    {
        $cycle = $cycleId === null ? '' : ' AND e.cycle_id = :cycle_id';
        $params = [':subject_id' => $subjectId, ':limit' => $limit, ':offset' => $offset];
        if ($cycleId !== null) {
            $params[':cycle_id'] = $cycleId;
        }
        $sql = "SELECT users.id, users.school_id, users.role, users.email, users.first_name, users.last_name, students.enrollment_number, students.birth_date, students.status, b.teacher_id FROM enrollment_subject_bindings b INNER JOIN enrollments e ON e.id = b.enrollment_id INNER JOIN users ON users.id = e.student_id INNER JOIN students ON students.user_id = users.id WHERE b.subject_id = :subject_id$cycle ORDER BY users.id LIMIT :limit OFFSET :offset";
        $rows = $this->db->createCommand($sql, $params)->queryAll();
        $countParams = array_diff_key($params, [':limit' => true, ':offset' => true]);
        $total = (int) $this->db->createCommand("SELECT COUNT(DISTINCT e.student_id) FROM enrollment_subject_bindings b INNER JOIN enrollments e ON e.id = b.enrollment_id WHERE b.subject_id = :subject_id$cycle", $countParams)->queryScalar();
        return ['data' => $rows, 'total' => $total];
    }
}
