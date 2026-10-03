<?php

declare(strict_types=1);

namespace App\StudentNotes\Controller;

use App\Auth\CurrentUser;
use App\StudentNotes\Service\StudentNoteException;
use App\StudentNotes\Service\StudentNoteService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class StudentNoteController
{
    public function __construct(private StudentNoteService $service, private ResponseFactoryInterface $responses)
    {
    }

    public function index(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->list(
            $this->user($request),
            $id,
            (int) ($query['page'] ?? 1),
            (int) ($query['per_page'] ?? 20),
        ));
    }

    public function create(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);
        return $this->run(fn () => $this->service->create(
            $this->user($request), $id, is_array($body) ? $body : [],
        ), 201);
    }

    private function user(ServerRequestInterface $request): CurrentUser
    {
        return $request->getAttribute(CurrentUser::class);
    }

    private function run(callable $action, int $status = 200): ResponseInterface
    {
        try {
            return \App\Shared\JsonResponse::send($this->responses, $status, $action());
        } catch (StudentNoteException $exception) {
            $code = match ($exception->status) {
                400 => 'VALIDATION_ERROR', 403 => 'FORBIDDEN', 404 => 'NOT_FOUND', default => 'INTERNAL_ERROR'
            };
            return \App\Shared\JsonResponse::error($this->responses, $exception->status, $exception->getMessage(), $code);
        } catch (\Throwable) {
            return \App\Shared\JsonResponse::error($this->responses, 500, 'Unexpected server error.');
        }
    }
}
