<?php

declare(strict_types=1);

namespace App\Health\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class HealthController
{
    public function __invoke(ResponseFactoryInterface $responseFactory): ResponseInterface
    {
        $response = $responseFactory->createResponse(200)->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR));
        return $response;
    }
}
