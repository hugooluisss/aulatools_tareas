<?php

declare(strict_types=1);

namespace App\Tests;

use App\Health\Controller\HealthController;
use HttpSoft\Message\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class HealthControllerTest extends TestCase
{
    public function testHealthResponse(): void
    {
        $response = (new HealthController())(new ResponseFactory());
        self::assertSame(200, $response->getStatusCode());
    }
}
