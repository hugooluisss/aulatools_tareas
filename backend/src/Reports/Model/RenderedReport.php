<?php

declare(strict_types=1);

namespace App\Reports\Model;

final class RenderedReport
{
    public function __construct(
        public readonly string $content,
        public readonly string $contentType,
        public readonly string $filename,
    ) {
    }
}
