<?php

declare(strict_types=1);

namespace App\StudentNotes\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class StudentNoteRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function student(int $schoolId, int $studentId): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT students.user_id
            FROM students
            INNER JOIN users ON users.id = students.user_id
            WHERE students.user_id = :student_id AND users.school_id = :school_id
            SQL, [':student_id' => $studentId, ':school_id' => $schoolId])->queryOne() ?: null;
    }

    public function teacherHasStudent(int $teacherId, int $studentId): bool
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT 1
            FROM enrollment_subject_bindings
            INNER JOIN enrollments ON enrollments.id = enrollment_subject_bindings.enrollment_id
            INNER JOIN subjects ON subjects.id = enrollment_subject_bindings.subject_id
            WHERE enrollments.student_id = :student_id
              AND (enrollment_subject_bindings.teacher_id = :teacher_id OR subjects.teacher_id = :teacher_id)
            LIMIT 1
            SQL, [':student_id' => $studentId, ':teacher_id' => $teacherId])->queryOne() !== null;
    }

    public function list(int $studentId, int $offset, int $limit): array
    {
        $data = $this->db->createCommand(<<<'SQL'
            SELECT student_notes.id, student_notes.student_id, student_notes.body, student_notes.created_at,
                   users.id AS author_id, users.first_name, users.last_name, users.role
            FROM student_notes
            INNER JOIN users ON users.id = student_notes.author_id
            WHERE student_notes.student_id = :student_id
            ORDER BY student_notes.created_at DESC, student_notes.id DESC
            LIMIT :limit OFFSET :offset
            SQL, [':student_id' => $studentId, ':limit' => $limit, ':offset' => $offset])->queryAll();
        $total = (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM student_notes WHERE student_id = :student_id',
            [':student_id' => $studentId],
        )->queryScalar();
        return ['data' => $data, 'total' => $total];
    }

    public function create(int $studentId, int $authorId, string $body): int
    {
        $this->db->createCommand(<<<'SQL'
            INSERT INTO student_notes (student_id, author_id, body, created_at)
            VALUES (:student_id, :author_id, :body, UTC_TIMESTAMP())
            SQL, [':student_id' => $studentId, ':author_id' => $authorId, ':body' => $body])->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function find(int $id): ?array
    {
        return $this->db->createCommand(<<<'SQL'
            SELECT student_notes.id, student_notes.student_id, student_notes.body, student_notes.created_at,
                   users.id AS author_id, users.first_name, users.last_name, users.role
            FROM student_notes
            INNER JOIN users ON users.id = student_notes.author_id
            WHERE student_notes.id = :id
            SQL, [':id' => $id])->queryOne() ?: null;
    }
}
