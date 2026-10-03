<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Service\PasswordResetService;
use App\Shared\JsonResponse;
use DomainException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PasswordResetController
{
    public function __construct(private PasswordResetService $service, private ResponseFactoryInterface $responses)
    {
    }

    public function request(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->body($request);
        $this->service->request(is_string($data['email'] ?? null) ? $data['email'] : '');
        return JsonResponse::send($this->responses, 200, ['message' => 'If the email is registered, a reset link will be sent.']);
    }

    public function reset(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->body($request);
        try {
            $this->service->reset(is_string($data['token'] ?? null) ? $data['token'] : '', is_string($data['password'] ?? null) ? $data['password'] : '');
            return JsonResponse::send($this->responses, 200, ['message' => 'Password updated.']);
        } catch (DomainException $exception) {
            return JsonResponse::error($this->responses, 400, $exception->getMessage());
        }
    }

    private function body(ServerRequestInterface $request): array
    {
        $decoded = json_decode((string) $request->getBody(), true);
        return is_array($decoded) ? $decoded : [];
    }
}
