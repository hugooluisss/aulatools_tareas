<?php

declare(strict_types=1);

namespace App\Auth\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;

final class JwtService
{
    private string $secret;

    public function __construct()
    {
        $secret = getenv('JWT_SECRET');
        if (!is_string($secret) || strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET must contain at least 32 bytes.');
        }
        $this->secret = $secret;
    }

    public function issue(int $userId, string $role, int $schoolId): string
    {
        $now = time();
        return JWT::encode([
            'iat' => $now,
            'exp' => $now + 28800,
            'sub' => (string) $userId,
            'role' => $role,
            'school_id' => $schoolId,
        ], $this->secret, 'HS256');
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }
}
