<?php

declare(strict_types=1);

namespace App\Reports\Model;

class ReportViewModel
{
    public function __construct(
        public readonly string $title,
        public readonly array $header,
        public readonly array $tables,
        public readonly array $sections = [],
    ) {
    }
}
