<?php

declare(strict_types=1);

namespace App\StudyPlans\Controller;

use App\Auth\CurrentUser;
use App\Shared\JsonResponse;
use App\StudyPlans\Service\StudyPlanException;
use App\StudyPlans\Service\StudyPlanService;
use DomainException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class StudyPlanController
{
    public function __construct(private StudyPlanService $service, private ResponseFactoryInterface $responseFactory)
    {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->list($this->user($request)));
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->create($this->user($request), $this->body($request)), 201);
    }

    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->view($this->user($request), $id));
    }

    public function update(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->update($this->user($request), $id, $this->body($request)));
    }

    public function delete(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(function () use ($request, $id): null {
            $this->service->delete($this->user($request), $id);
            return null;
        }, 204);
    }

    private function user(ServerRequestInterface $request): CurrentUser
    {
        return $request->getAttribute(CurrentUser::class);
    }

    private function body(ServerRequestInterface $request): array
    {
        $body = json_decode((string) $request->getBody(), true);
        return is_array($body) ? $body : [];
    }

    private function run(callable $action, int $success = 200): ResponseInterface
    {
        try {
            $result = $action();
            return $success === 204 ? $this->responseFactory->createResponse(204) : JsonResponse::send($this->responseFactory, $success, $result);
        } catch (StudyPlanException $exception) {
            return JsonResponse::error($this->responseFactory, $exception->status, $exception->getMessage());
        } catch (DomainException $exception) {
            return JsonResponse::error($this->responseFactory, 404, $exception->getMessage());
        } catch (\InvalidArgumentException $exception) {
            return JsonResponse::error($this->responseFactory, 400, $exception->getMessage());
        } catch (\Throwable) {
            return JsonResponse::error($this->responseFactory, 500, 'Unexpected server error.');
        }
    }
}
