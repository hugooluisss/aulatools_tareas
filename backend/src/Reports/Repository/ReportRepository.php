<?php

declare(strict_types=1);

namespace App\Reports\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class ReportRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function options(int $schoolId, string $role, int $userId): array
    {
        $teacher = $role === 'teacher' ? ' AND subjects.teacher_id = :teacher_id' : '';
        $params = [':group_school_id' => $schoolId, ':subject_school_id' => $schoolId];
        if ($role === 'teacher') {
            $params[':teacher_id'] = $userId;
        }
        $rows = $this->db->createCommand(<<<SQL
            SELECT DISTINCT `groups`.id AS group_id, `groups`.name AS group_name,
                   subjects.id AS subject_id, subjects.code, subjects.name AS subject_name
            FROM `groups`
            INNER JOIN academic_cycles ON academic_cycles.id = `groups`.cycle_id
            INNER JOIN group_subjects ON group_subjects.group_id = `groups`.id
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            INNER JOIN enrollments ON enrollments.group_id = `groups`.id
              AND enrollments.cycle_id = academic_cycles.id
            INNER JOIN enrollment_subject_bindings ON enrollment_subject_bindings.enrollment_id = enrollments.id
              AND enrollment_subject_bindings.subject_id = subjects.id
            WHERE `groups`.school_id = :group_school_id AND subjects.school_id = :subject_school_id$teacher
            ORDER BY `groups`.name, subjects.name
            SQL, $params)->queryAll();
        $groups = [];
        foreach ($rows as $row) {
            $groupId = (int) $row['group_id'];
            $groups[$groupId] ??= ['id' => $groupId, 'name' => $row['group_name'], 'subjects' => []];
            $groups[$groupId]['subjects'][] = [
                'id' => (int) $row['subject_id'],
                'code' => $row['code'],
                'name' => $row['subject_name'],
            ];
        }
        return ['groups' => array_values($groups)];
    }

    public function group(int $schoolId, int $groupId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT `groups`.id, `groups`.name AS group_name, academic_cycles.name AS cycle_name,
                   schools.name AS school_name, schools.logo_path
            FROM `groups`
            INNER JOIN academic_cycles ON academic_cycles.id = `groups`.cycle_id
            INNER JOIN schools ON schools.id = academic_cycles.school_id
            WHERE `groups`.id = :group_id AND `groups`.school_id = :school_id
            SQL, [':group_id' => $groupId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function subject(int $schoolId, int $subjectId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id, subjects.code, subjects.name, subjects.teacher_id,
                   users.first_name AS teacher_first_name, users.last_name AS teacher_last_name
            FROM subjects
            LEFT JOIN users ON users.id = subjects.teacher_id
            WHERE subjects.id = :subject_id AND subjects.school_id = :school_id
            SQL, [':subject_id' => $subjectId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function teacherOwnsGroupSubject(int $teacherId, int $groupId, int $subjectId): bool
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id
            FROM subjects
            INNER JOIN group_subjects ON group_subjects.subject_id = subjects.id
            INNER JOIN `groups` ON `groups`.id = group_subjects.group_id
            INNER JOIN academic_cycles ON academic_cycles.id = `groups`.cycle_id
            WHERE subjects.id = :subject_id AND subjects.teacher_id = :teacher_id
              AND `groups`.id = :group_id AND subjects.school_id = `groups`.school_id
            SQL, [':subject_id' => $subjectId, ':teacher_id' => $teacherId, ':group_id' => $groupId])->queryOne() !== null;
    }

    public function groupHasSubject(int $groupId, int $subjectId): bool
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT group_subjects.subject_id
            FROM group_subjects
            INNER JOIN `groups` ON `groups`.id = group_subjects.group_id
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            WHERE group_subjects.group_id = :group_id AND group_subjects.subject_id = :subject_id
              AND `groups`.school_id = subjects.school_id
            SQL, [':group_id' => $groupId, ':subject_id' => $subjectId])->queryOne() !== null;
    }

    public function teacherOwnsGroup(int $teacherId, int $groupId): bool
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT `groups`.id
            FROM `groups`
            INNER JOIN group_subjects ON group_subjects.group_id = `groups`.id
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            WHERE `groups`.id = :group_id AND subjects.teacher_id = :teacher_id
              AND subjects.school_id = `groups`.school_id
            SQL, [':group_id' => $groupId, ':teacher_id' => $teacherId])->queryOne() !== null;
    }

    public function students(int $schoolId, int $groupId, ?int $subjectId, ?int $teacherId = null): array
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
        if ($teacherId !== null) {
            $subjectFilter .= <<<'SQL'
              AND EXISTS (
                  SELECT 1 FROM enrollment_subject_bindings AS teacher_bindings
                  INNER JOIN subjects AS teacher_subjects ON teacher_subjects.id = teacher_bindings.subject_id
                  WHERE teacher_bindings.enrollment_id = enrollments.id
                    AND teacher_subjects.teacher_id = :teacher_id
              )
            SQL;
            $params[':teacher_id'] = $teacherId;
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

    public function taskSubject(int $schoolId, int $subjectId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id, subjects.name AS subject_name, subjects.teacher_id,
                   users.first_name AS teacher_first_name, users.last_name AS teacher_last_name,
                   schools.name AS school_name
            FROM subjects
            LEFT JOIN users ON users.id = subjects.teacher_id
            INNER JOIN schools ON schools.id = subjects.school_id
            WHERE subjects.id = :subject_id AND subjects.school_id = :school_id
            SQL, [':subject_id' => $subjectId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function enrolledStudents(int $schoolId, int $subjectId, array $studentIds): array
    {
        $ids = $this->placeholders($studentIds, 'student', $params);
        $params[':subject_id'] = $subjectId;
        $params[':school_id'] = $schoolId;
        return $this->db->createCommand(<<<SQL
            SELECT DISTINCT users.id, users.first_name, users.last_name, students.enrollment_number,
                   enrollments.cycle_id, academic_cycles.name AS cycle_name
            FROM enrollment_subject_bindings
            INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
            INNER JOIN academic_cycles ON academic_cycles.id = enrollments.cycle_id
            INNER JOIN users ON users.id = enrollments.student_id
            INNER JOIN students ON students.user_id = users.id
            WHERE enrollment_subject_bindings.subject_id = :subject_id AND users.id IN ($ids)
              AND academic_cycles.school_id = :school_id
              AND students.status = 'active'
            ORDER BY users.last_name, users.first_name, users.id
            SQL, $params)->queryAll();
    }

    public function tasks(int $subjectId, array $studentIds): array
    {
        $ids = $this->placeholders($studentIds, 'student', $params);
        $params[':subject_id'] = $subjectId;
        return $this->db->createCommand(<<<SQL
            SELECT tasks.name AS task_name, DATE(tasks.due_at) AS due_date, tasks.cycle_id,
                   task_deliveries.student_id AS student_id, DATE(task_deliveries.delivered_at) AS delivered_date,
                   task_deliveries.grade
            FROM tasks
            LEFT JOIN task_deliveries ON task_deliveries.task_id = tasks.id
              AND task_deliveries.student_id IN ($ids)
            WHERE tasks.subject_id = :subject_id AND tasks.status <> 'cancelled'
            ORDER BY tasks.due_at, tasks.id
            SQL, $params)->queryAll();
    }

    private function placeholders(array $values, string $prefix, ?array &$params): string
    {
        $holders = [];
        $params = [];
        foreach (array_values($values) as $index => $value) {
            $holder = ':' . $prefix . $index;
            $holders[] = $holder;
            $params[$holder] = (int) $value;
        }
        return implode(', ', $holders);
    }
}
