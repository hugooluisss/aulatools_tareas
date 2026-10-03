<?php

declare(strict_types=1);

namespace App\Reports\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class AttendanceReportRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function group(int $schoolId, int $groupId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT `groups`.id, `groups`.name AS group_name, academic_cycles.name AS cycle_name, schools.name AS school_name, schools.logo_path
            FROM `groups`
            INNER JOIN academic_cycles ON academic_cycles.id = `groups`.cycle_id
            INNER JOIN schools ON schools.id = academic_cycles.school_id
            WHERE `groups`.id = :group_id AND `groups`.school_id = :school_id
            SQL, [':group_id' => $groupId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function subject(int $schoolId, int $subjectId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id, subjects.name, users.first_name AS teacher_first_name, users.last_name AS teacher_last_name
            FROM subjects
            LEFT JOIN users ON users.id = subjects.teacher_id
            WHERE subjects.id = :subject_id AND subjects.school_id = :school_id
            SQL, [':subject_id' => $subjectId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function students(int $schoolId, int $groupId, ?int $subjectId): array
    {
        $subjectFilter = $subjectId === null ? '' : <<<'SQL'
              AND EXISTS (
                  SELECT 1 FROM enrollment_subject_bindings
                  WHERE enrollment_subject_bindings.enrollment_id = enrollments.id
                    AND enrollment_subject_bindings.subject_id = :subject_id
              )
            SQL;
        $params = [':group_id' => $groupId, ':school_id' => $schoolId];
        if ($subjectId !== null) {
            $params[':subject_id'] = $subjectId;
        }

        return $this->db->createCommand(<<<'SQL'
            SELECT users.first_name, users.last_name, students.enrollment_number
            FROM enrollments
            INNER JOIN academic_cycles ON academic_cycles.id = enrollments.cycle_id
            INNER JOIN users ON users.id = enrollments.student_id
            INNER JOIN students ON students.user_id = users.id
            WHERE enrollments.group_id = :group_id AND academic_cycles.school_id = :school_id
              AND users.role = 'student' AND students.status = 'active'
            SQL . $subjectFilter . <<<'SQL'
            ORDER BY users.last_name, users.first_name, users.id
            SQL, $params)->queryAll();
    }
}
