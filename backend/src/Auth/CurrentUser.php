<?php

declare(strict_types=1);

namespace App\Auth;

final class CurrentUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $role,
        public readonly int $schoolId,
    ) {
    }
}
