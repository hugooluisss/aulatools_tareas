<?php

declare(strict_types=1);

namespace App\Health\Service;

final class HealthService
{
    public function status(): array
    {
        return ['status' => 'ok'];
    }
}
