<?php

declare(strict_types=1);

namespace App\Enrollments\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class EnrollmentRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function group(int $schoolId, int $groupId): ?array
    {
        return $this->db->createCommand(
            'SELECT id FROM `groups` WHERE id = :id AND school_id = :school_id',
            [':id' => $groupId, ':school_id' => $schoolId],
        )->queryOne();
    }

    public function studentBelongsToSchool(int $schoolId, int $studentId): bool
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT users.id
            FROM users
            INNER JOIN students ON students.user_id = users.id
            WHERE users.id = :id
                AND users.school_id = :school_id
                AND users.role = 'student'
            SQL, [':id' => $studentId, ':school_id' => $schoolId])->queryOne() !== null;
    }

    public function groupSubjects(int $groupId, int $schoolId): array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id
            FROM group_subjects
            INNER JOIN subjects ON subjects.id = group_subjects.subject_id
            INNER JOIN academic_cycles ON academic_cycles.id = subjects.cycle_id
            WHERE group_subjects.group_id = :group_id AND academic_cycles.school_id = :school_id
            ORDER BY subjects.id
            SQL, [':group_id' => $groupId, ':school_id' => $schoolId])->queryAll();
    }

    public function subject(int $schoolId, int $subjectId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT subjects.id
            FROM subjects
            INNER JOIN academic_cycles ON academic_cycles.id = subjects.cycle_id
            WHERE subjects.id = :id AND academic_cycles.school_id = :school_id
            SQL, [':id' => $subjectId, ':school_id' => $schoolId])->queryOne();
    }

    public function enroll(int $studentId, int $subjectId, ?int $groupId): void
    {
        $this->db->createCommand(<<<'SQL'
            INSERT INTO enrollments (student_id, subject_id, source_group_id)
            VALUES (:student_id, :subject_id, :source_group_id)
            ON DUPLICATE KEY UPDATE student_id = VALUES(student_id)
            SQL, [':student_id' => $studentId, ':subject_id' => $subjectId, ':source_group_id' => $groupId])->execute();
    }

    public function unenroll(int $studentId, int $subjectId): void
    {
        $this->db->createCommand(
            'DELETE FROM enrollments WHERE student_id = :student_id AND subject_id = :subject_id',
            [':student_id' => $studentId, ':subject_id' => $subjectId],
        )->execute();
    }
}
