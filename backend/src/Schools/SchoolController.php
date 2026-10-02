<?php

declare(strict_types=1);

namespace App\Schools;

use App\Auth\CurrentUser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class SchoolController
{
    public function __construct(private ConnectionInterface $db, private ResponseFactoryInterface $responses)
    {
    }

    public function view(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json(200, $this->find($this->schoolId($request)));
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);
        $name = is_array($body) && is_string($body['name'] ?? null) ? trim($body['name']) : '';
        if ($name === '' || mb_strlen($name) > 255) {
            return \App\Shared\JsonResponse::error($this->responses, 400, 'Invalid school name.');
        }
        $schoolId = $this->schoolId($request);
        $this->db->createCommand('UPDATE schools SET name = :name WHERE id = :id', [':name' => $name, ':id' => $schoolId])->execute();
        return $this->json(200, $this->find($schoolId));
    }

    private function find(int $id): array
    {
        return $this->db->createCommand('SELECT id, name FROM schools WHERE id = :id', [':id' => $id])->queryOne();
    }

    private function schoolId(ServerRequestInterface $request): int
    {
        return $request->getAttribute(CurrentUser::class)->schoolId;
    }

    private function json(int $status, mixed $data): ResponseInterface
    {
        return \App\Shared\JsonResponse::send($this->responses, $status, $data);
    }
}
