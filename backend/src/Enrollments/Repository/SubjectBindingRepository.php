<?php

declare(strict_types=1);

namespace App\Enrollments\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class SubjectBindingRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function studentBelongsToSchool(int $schoolId, int $studentId): bool
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT users.id FROM users
            INNER JOIN students ON students.user_id = users.id
            WHERE users.id = :id AND users.school_id = :school_id AND users.role = 'student'
            SQL, [':id' => $studentId, ':school_id' => $schoolId])->queryOne() !== null;
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
            WHERE users.school_id = :school_id AND users.role = 'student'
              AND users.id IN ({$in})
            SQL, $params)->queryAll();
    }

    public function subject(int $schoolId, int $subjectId): ?array
    {
        return $this->db->createCommand(
            'SELECT id FROM subjects WHERE id = :id AND school_id = :school_id',
            [':id' => $subjectId, ':school_id' => $schoolId],
        )->queryOne() ?: null;
    }

    public function cycle(int $schoolId, int $cycleId): ?array
    {
        return $this->db->createCommand(
            'SELECT id, status FROM academic_cycles WHERE id = :id AND school_id = :school_id',
            [':id' => $cycleId, ':school_id' => $schoolId],
        )->queryOne() ?: null;
    }

    public function studentsWithCycleInscription(int $schoolId, int $cycleId, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }
        [$in, $params] = $this->inParams($studentIds, 'student');
        $params[':school_id'] = $schoolId;
        $params[':cycle_id'] = $cycleId;
        return $this->db->createCommand(<<<SQL
            SELECT enrollments.id AS enrollment_id, enrollments.student_id
            FROM enrollments
            WHERE enrollments.cycle_id = :cycle_id AND enrollments.student_id IN ({$in})
              AND EXISTS (SELECT 1 FROM academic_cycles WHERE id = enrollments.cycle_id AND school_id = :school_id)
            SQL, $params)->queryAll();
    }

    public function enrolledStudents(int $subjectId, int $cycleId, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }
        [$in, $params] = $this->inParams($studentIds, 'student');
        $params[':subject_id'] = $subjectId;
        $params[':cycle_id'] = $cycleId;
        return $this->db->createCommand(<<<SQL
            SELECT enrollments.student_id FROM enrollment_subject_bindings bindings
            INNER JOIN enrollments ON enrollments.id = bindings.enrollment_id
            WHERE bindings.subject_id = :subject_id AND enrollments.cycle_id = :cycle_id
              AND enrollments.student_id IN ({$in})
            SQL, $params)->queryAll();
    }

    public function bindStudents(int $subjectId, int $cycleId, array $studentIds): void
    {
        if ($studentIds === []) {
            return;
        }
        [$in, $params] = $this->inParams($studentIds, 'student');
        $params[':subject_id'] = $subjectId;
        $params[':cycle_id'] = $cycleId;
        $this->db->createCommand(<<<SQL
            INSERT IGNORE INTO enrollment_subject_bindings (enrollment_id, subject_id, teacher_id)
            SELECT enrollments.id, :subject_id, COALESCE(groups_table.teacher_id, subjects.teacher_id)
            FROM enrollments
            INNER JOIN `groups` AS groups_table ON groups_table.id = enrollments.group_id
            INNER JOIN subjects ON subjects.id = :subject_id
            WHERE enrollments.cycle_id = :cycle_id AND enrollments.student_id IN ({$in})
            SQL, $params)->execute();
    }

    public function bindStudent(int $subjectId, int $cycleId, int $studentId): bool
    {
        return $this->db->createCommand(<<<'SQL'
            INSERT IGNORE INTO enrollment_subject_bindings (enrollment_id, subject_id, teacher_id)
            SELECT enrollments.id, :subject_id, COALESCE(groups_table.teacher_id, subjects.teacher_id)
            FROM enrollments
            INNER JOIN `groups` AS groups_table ON groups_table.id = enrollments.group_id
            INNER JOIN subjects ON subjects.id = :subject_id
            WHERE enrollments.student_id = :student_id AND enrollments.cycle_id = :cycle_id
            SQL, [':subject_id' => $subjectId, ':student_id' => $studentId, ':cycle_id' => $cycleId])->execute() > 0;
    }

    public function teacherBelongsToSchool(int $schoolId, int $teacherId): bool
    {
        return $this->db->createCommand("SELECT id FROM users WHERE id = :id AND school_id = :school_id AND role = 'teacher'", [':id' => $teacherId, ':school_id' => $schoolId])->queryOne() !== null;
    }

    public function bindingStudents(int $subjectId, int $cycleId, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }
        [$in, $params] = $this->inParams($studentIds, 'student');
        $params[':subject_id'] = $subjectId;
        $params[':cycle_id'] = $cycleId;
        return $this->db->createCommand(<<<SQL
            SELECT enrollments.student_id FROM enrollment_subject_bindings
            INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
            WHERE enrollment_subject_bindings.subject_id = :subject_id AND enrollments.cycle_id = :cycle_id
              AND enrollments.student_id IN ({$in})
            SQL, $params)->queryAll();
    }

    public function reassignTeachers(int $subjectId, int $cycleId, array $studentIds, int $teacherId): int
    {
        if ($studentIds === []) {
            return 0;
        }
        [$in, $params] = $this->inParams($studentIds, 'student');
        $params[':subject_id'] = $subjectId;
        $params[':cycle_id'] = $cycleId;
        $params[':teacher_id'] = $teacherId;
        return $this->db->createCommand(<<<SQL
            UPDATE enrollment_subject_bindings
            INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
            SET enrollment_subject_bindings.teacher_id = :teacher_id
            WHERE enrollment_subject_bindings.subject_id = :subject_id AND enrollments.cycle_id = :cycle_id
              AND enrollments.student_id IN ({$in})
            SQL, $params)->execute();
    }

    public function unbindStudent(int $subjectId, int $cycleId, int $studentId): void
    {
        $this->db->createCommand(<<<'SQL'
            DELETE bindings FROM enrollment_subject_bindings bindings
            INNER JOIN enrollments ON enrollments.id = bindings.enrollment_id
            WHERE enrollments.student_id = :student_id AND enrollments.cycle_id = :cycle_id
              AND bindings.subject_id = :subject_id
            SQL, [':student_id' => $studentId, ':cycle_id' => $cycleId, ':subject_id' => $subjectId])->execute();
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
}
