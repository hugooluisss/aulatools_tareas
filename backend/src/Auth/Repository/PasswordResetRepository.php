<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use Yiisoft\Db\Connection\ConnectionInterface;

class PasswordResetRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    public function findUserByEmail(string $email): ?array
    {
        return $this->db->createCommand('SELECT id FROM users WHERE email = :email', [':email' => $email])->queryOne();
    }

    public function invalidatePending(int $userId, string $now): void
    {
        $this->db->createCommand('UPDATE password_reset_tokens SET used_at = :now WHERE user_id = :user_id AND used_at IS NULL', [':now' => $now, ':user_id' => $userId])->execute();
    }

    public function create(int $userId, string $hash, string $expiresAt, string $now): void
    {
        $this->db->createCommand('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at) VALUES (:user_id, :hash, :expires_at, :now)', [':user_id' => $userId, ':hash' => $hash, ':expires_at' => $expiresAt, ':now' => $now])->execute();
    }

    public function findValid(string $hash, string $now): ?array
    {
        return $this->db->createCommand('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = :hash AND used_at IS NULL AND expires_at > :now FOR UPDATE', [':hash' => $hash, ':now' => $now])->queryOne();
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->db->createCommand('UPDATE users SET password_hash = :hash WHERE id = :id', [':hash' => $passwordHash, ':id' => $userId])->execute();
    }

    public function markUsed(int $tokenId, string $now): void
    {
        $this->db->createCommand('UPDATE password_reset_tokens SET used_at = :now WHERE id = :id', [':now' => $now, ':id' => $tokenId])->execute();
    }
}
