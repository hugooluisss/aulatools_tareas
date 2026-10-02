<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class AuthRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function emailExists(string $email): bool
    {
        return $this->db->createCommand(
            'SELECT id FROM users WHERE email = :email',
            [':email' => $email],
        )->queryOne() !== null;
    }

    public function createSchool(string $name): int
    {
        $this->db->createCommand('INSERT INTO schools (name) VALUES (:name)', [':name' => $name])->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function createUser(
        int $schoolId,
        string $email,
        string $passwordHash,
        string $firstName,
        string $lastName,
    ): int {
        $this->db->createCommand(
            <<<'SQL'
                INSERT INTO users (school_id, role, email, password_hash, first_name, last_name)
                VALUES (:school_id, 'admin', :email, :password_hash, :first_name, :last_name)
                SQL,
            [
                ':school_id' => $schoolId,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
            ],
        )->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->createCommand(
            <<<'SQL'
                SELECT users.id, users.school_id, users.role, users.email, users.password_hash,
                       students.status AS student_status
                FROM users
                LEFT JOIN students ON students.user_id = users.id
                WHERE users.email = :email
                SQL,
            [':email' => $email],
        )->queryOne();
    }

    public function findPasswordHashById(int $userId): ?array
    {
        return $this->db->createCommand(
            'SELECT password_hash FROM users WHERE id = :id',
            [':id' => $userId],
        )->queryOne();
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->db->createCommand(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id',
            [':password_hash' => $passwordHash, ':id' => $userId],
        )->execute();
    }
}
