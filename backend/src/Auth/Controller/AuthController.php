<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\CurrentUser;
use App\Auth\Service\AuthService;
use DomainException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController
{
    public function __construct(
        private AuthService $authService,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function registerSchool(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->authService->registerSchool($this->body($request));
            return $this->json(201, ['message' => 'School registered.']);
        } catch (DomainException $exception) {
            $status = str_contains($exception->getMessage(), 'already registered') ? 409 : 400;
            return \App\Shared\JsonResponse::error($this->responseFactory, $status, $exception->getMessage());
        }
    }

    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->body($request);
        try {
            $token = $this->authService->login((string) ($data['email'] ?? ''), (string) ($data['password'] ?? ''));
            return $this->json(200, ['token' => $token]);
        } catch (DomainException) {
            return \App\Shared\JsonResponse::error($this->responseFactory, 401, 'Invalid email or password.');
        }
    }

    public function changePassword(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->body($request);
        $user = $request->getAttribute(CurrentUser::class);
        try {
            $this->authService->changePassword(
                $user,
                (string) ($data['current_password'] ?? ''),
                (string) ($data['new_password'] ?? ''),
            );
            return $this->json(200, ['message' => 'Password updated.']);
        } catch (DomainException $exception) {
            $status = str_contains($exception->getMessage(), 'incorrect') ? 401 : 422;
            return \App\Shared\JsonResponse::error($this->responseFactory, $status, $exception->getMessage());
        }
    }

    private function body(ServerRequestInterface $request): array
    {
        $decoded = json_decode((string) $request->getBody(), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function json(int $status, mixed $data): ResponseInterface
    {
        return \App\Shared\JsonResponse::send($this->responseFactory, $status, $data);
    }
}
