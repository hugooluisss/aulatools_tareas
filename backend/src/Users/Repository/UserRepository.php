<?php

declare(strict_types=1);

namespace App\Users\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class UserRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function list(int $schoolId, string $role, int $offset, int $limit, ?string $status = null): array
    {
        $where = 'users.school_id = :school_id AND users.role = :role';
        $params = [':school_id' => $schoolId, ':role' => $role, ':offset' => $offset, ':limit' => $limit];
        if ($role === 'student' && $status !== null) {
            $where .= ' AND students.status = :status';
            $params[':status'] = $status;
        }
        $listSql = <<<SQL
            {$this->listSelectSql($role)}
            WHERE {$where} ORDER BY users.id LIMIT :limit OFFSET :offset
            SQL;
        $rows = $this->db->createCommand(
            $listSql,
            $params,
        )->queryAll();
        $countSql = <<<SQL
            SELECT COUNT(*) FROM users {$this->joinSql($role)} WHERE {$where}
            SQL;
        $total = (int) $this->db->createCommand(
            $countSql,
            array_diff_key($params, [':offset' => true, ':limit' => true]),
        )->queryScalar();
        return ['data' => $rows, 'total' => $total];
    }

    public function find(int $schoolId, string $role, int $id): ?array
    {
        return $this->db->createCommand(
            $this->selectSql($role) . "\n" . <<<'SQL'
                WHERE users.school_id = :school_id AND users.role = :role AND users.id = :id
                SQL,
            [':school_id' => $schoolId, ':role' => $role, ':id' => $id],
        )->queryOne() ?: null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = <<<'SQL'
            SELECT id FROM users WHERE email = :email
            SQL;
        $params = [':email' => $email];
        if ($excludeId !== null) {
            $sql .= <<<'SQL'
                 AND id <> :exclude_id
                SQL;
            $params[':exclude_id'] = $excludeId;
        }
        return $this->db->createCommand($sql, $params)->queryOne() !== null;
    }

    public function enrollmentExists(int $schoolId, string $number, ?int $excludeId = null): bool
    {
        $sql = <<<'SQL'
            SELECT students.user_id
            FROM students
            INNER JOIN users ON users.id = students.user_id
            WHERE users.school_id = :school_id AND students.enrollment_number = :number
            SQL;
        $params = [':school_id' => $schoolId, ':number' => $number];
        if ($excludeId !== null) {
            $sql .= <<<'SQL'
                 AND students.user_id <> :exclude_id
                SQL;
            $params[':exclude_id'] = $excludeId;
        }
        return $this->db->createCommand($sql, $params)->queryOne() !== null;
    }

    public function create(int $schoolId, string $role, array $data, string $hash): int
    {
        $this->db->createCommand(
            <<<'SQL'
                INSERT INTO users (school_id, role, email, password_hash, first_name, last_name)
                VALUES (:school_id, :role, :email, :password_hash, :first_name, :last_name)
                SQL,
            [':school_id' => $schoolId, ':role' => $role, ':email' => $data['email'], ':password_hash' => $hash,
                ':first_name' => $data['first_name'], ':last_name' => $data['last_name']],
        )->execute();
        $id = (int) $this->db->getLastInsertId();
        if ($role === 'student') {
            $this->db->createCommand(
                <<<'SQL'
                    INSERT INTO students (user_id, enrollment_number, birth_date, status)
                    VALUES (:user_id, :user_id, :birth_date, :status)
                    SQL,
                [
                    ':user_id' => $id,
                    ':birth_date' => $data['birth_date'],
                    ':status' => $data['status'] ?? 'active',
                ],
            )->execute();
        }
        return $id;
    }

    public function update(int $schoolId, string $role, int $id, array $data): void
    {
        $this->db->createCommand(
            <<<'SQL'
                UPDATE users SET email = :email, first_name = :first_name, last_name = :last_name
                WHERE id = :id AND school_id = :school_id AND role = :role
                SQL,
            [':email' => $data['email'], ':first_name' => $data['first_name'], ':last_name' => $data['last_name'],
                ':id' => $id, ':school_id' => $schoolId, ':role' => $role],
        )->execute();
        if ($role === 'student') {
            $this->db->createCommand(
                <<<'SQL'
                    UPDATE students
                    SET birth_date = :birth_date, status = :status
                    WHERE user_id = :id
                    SQL,
                [
                    ':birth_date' => $data['birth_date'],
                    ':status' => $data['status'],
                    ':id' => $id,
                ],
            )->execute();
        }
    }

    public function setStudentStatus(int $schoolId, int $id, string $status): void
    {
        $this->db->createCommand(
            <<<'SQL'
                UPDATE students
                INNER JOIN users ON users.id = students.user_id
                SET students.status = :status
                WHERE users.id = :id AND users.school_id = :school_id AND users.role = 'student'
                SQL,
            [':status' => $status, ':id' => $id, ':school_id' => $schoolId],
        )->execute();
    }

    public function delete(int $schoolId, string $role, int $id): void
    {
        $this->db->createCommand(
            <<<'SQL'
                DELETE FROM users WHERE id = :id AND school_id = :school_id AND role = :role
                SQL,
            [':id' => $id, ':school_id' => $schoolId, ':role' => $role],
        )->execute();
    }

    public function updatePassword(int $schoolId, int $id, string $hash): bool
    {
        return $this->db->createCommand(
            <<<'SQL'
                UPDATE users SET password_hash = :hash WHERE id = :id AND school_id = :school_id
                SQL,
            [':hash' => $hash, ':id' => $id, ':school_id' => $schoolId],
        )->execute() > 0;
    }

    private function selectSql(string $role): string
    {
        return <<<SQL
            SELECT users.id, users.school_id, users.role, users.email, users.first_name, users.last_name
                   {$this->studentColumns($role)}
            FROM users {$this->joinSql($role)}
            SQL;
    }

    private function listSelectSql(string $role): string
    {
        if ($role !== 'student') {
            return $this->selectSql($role);
        }

        return <<<'SQL'
            SELECT users.id, users.school_id, users.role, users.email, users.first_name, users.last_name,
                   students.enrollment_number, students.birth_date, students.status,
                   active_enrollments.id AS enrollment_id,
                   active_enrollments.cycle_id AS enrollment_cycle_id,
                   active_enrollments.cycle_name AS enrollment_cycle_name,
                   active_enrollments.group_id AS enrollment_group_id,
                   active_enrollments.group_name AS enrollment_group_name
            FROM users
            INNER JOIN students ON students.user_id = users.id
            LEFT JOIN (
                SELECT ranked.id, ranked.student_id, ranked.cycle_id, ranked.cycle_name,
                       ranked.group_id, ranked.group_name
                FROM (
                    SELECT enrollments.id, enrollments.student_id, enrollments.cycle_id,
                           academic_cycles.name AS cycle_name, enrollments.group_id, `groups`.name AS group_name,
                           ROW_NUMBER() OVER (
                               PARTITION BY enrollments.student_id ORDER BY enrollments.created_at DESC, enrollments.id DESC
                           ) AS enrollment_rank
                    FROM enrollments
                    INNER JOIN academic_cycles ON academic_cycles.id = enrollments.cycle_id
                    INNER JOIN `groups` ON `groups`.id = enrollments.group_id
                    WHERE academic_cycles.status = 'active'
                ) AS ranked
                WHERE ranked.enrollment_rank = 1
            ) AS active_enrollments ON active_enrollments.student_id = users.id
            SQL;
    }

    private function joinSql(string $role): string
    {
        return $role === 'student' ? 'INNER JOIN students ON students.user_id = users.id' : '';
    }

    private function studentColumns(string $role): string
    {
        return $role === 'student' ? ', students.enrollment_number, students.birth_date, students.status' : '';
    }
}
