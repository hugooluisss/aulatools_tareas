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
        return $this->json(200, ['data' => $this->find($this->schoolId($request))]);
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);
        $name = is_array($body) && is_string($body['name'] ?? null) ? trim($body['name']) : '';
        if ($name === '' || mb_strlen($name) > 255) {
            return $this->json(400, ['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Invalid school name.']]);
        }
        $schoolId = $this->schoolId($request);
        $this->db->createCommand('UPDATE schools SET name = :name WHERE id = :id', [':name' => $name, ':id' => $schoolId])->execute();
        return $this->json(200, ['data' => $this->find($schoolId)]);
    }

    private function find(int $id): array
    {
        return $this->db->createCommand('SELECT id, name FROM schools WHERE id = :id', [':id' => $id])->queryOne();
    }

    private function schoolId(ServerRequestInterface $request): int
    {
        return $request->getAttribute(CurrentUser::class)->schoolId;
    }

    private function json(int $status, array $data): ResponseInterface
    {
        $response = $this->responses->createResponse($status)->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response;
    }
}
