<?php

declare(strict_types=1);

namespace App\Reports\Controller;

use App\Auth\CurrentUser;
use App\Reports\Service\AttendanceReportService;
use App\Reports\Service\AttendanceReportForbiddenException;
use App\Reports\Service\TaskReportCardService;
use App\Shared\JsonResponse;
use DomainException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ReportController
{
    public function __construct(
        private AttendanceReportService $service,
        private TaskReportCardService $taskReportCardService,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function options(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return JsonResponse::send($this->responseFactory, 200, $this->service->options($this->user($request)));
        } catch (DomainException $exception) {
            return JsonResponse::error($this->responseFactory, 403, $exception->getMessage());
        } catch (\Throwable) {
            return JsonResponse::error($this->responseFactory, 500, 'Unexpected server error.');
        }
    }

    public function attendance(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $report = $this->service->generate($this->user($request), $request->getQueryParams());
            $response = $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', "attachment; filename=\"attendance-{$report['group_id']}-{$report['start_date']}.pdf\"");
            $response->getBody()->write($report['bytes']);
            return $response;
        } catch (DomainException $exception) {
            return JsonResponse::error($this->responseFactory, 404, $exception->getMessage());
        } catch (AttendanceReportForbiddenException $exception) {
            return JsonResponse::error($this->responseFactory, 403, $exception->getMessage());
        } catch (\InvalidArgumentException $exception) {
            return JsonResponse::error($this->responseFactory, 400, $exception->getMessage());
        } catch (\Throwable) {
            return JsonResponse::error($this->responseFactory, 500, 'Unexpected server error.');
        }
    }

    public function taskReportCard(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $data = json_decode((string) $request->getBody(), true);
            if (!is_array($data)) {
                throw new \InvalidArgumentException('Invalid task report card parameters.');
            }
            $report = $this->taskReportCardService->generate($this->user($request), $data);
            $response = $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', $report['content_type'])
                ->withHeader('Content-Disposition', 'attachment; filename="' . $report['filename'] . '"');
            $response->getBody()->write($report['bytes']);
            return $response;
        } catch (DomainException $exception) {
            return JsonResponse::error($this->responseFactory, 404, $exception->getMessage());
        } catch (AttendanceReportForbiddenException $exception) {
            return JsonResponse::error($this->responseFactory, 403, $exception->getMessage());
        } catch (\InvalidArgumentException $exception) {
            return JsonResponse::error($this->responseFactory, 400, $exception->getMessage());
        } catch (\Throwable) {
            return JsonResponse::error($this->responseFactory, 500, 'Unexpected server error.');
        }
    }

    private function user(ServerRequestInterface $request): CurrentUser
    {
        return $request->getAttribute(CurrentUser::class);
    }
}
