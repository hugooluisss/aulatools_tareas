<?php

declare(strict_types=1);

namespace App\Shared;

final class Paginator
{
    public static function parse(array $query): array
    {
        $page = filter_var($query['page'] ?? 1, FILTER_VALIDATE_INT);
        $perPage = filter_var($query['per_page'] ?? 20, FILTER_VALIDATE_INT);
        if ($page === false || $perPage === false || $page < 1 || $perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException('Invalid pagination.');
        }
        return ['page' => (int) $page, 'perPage' => (int) $perPage, 'offset' => ((int) $page - 1) * (int) $perPage];
    }

    public static function build(array $items, int $total, int $page, int $perPage): array
    {
        return ['items' => $items, 'page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => max(1, (int) ceil($total / $perPage))];
    }
}
