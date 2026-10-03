<?php

declare(strict_types=1);

namespace App\Reports\Controller;

use App\Auth\CurrentUser;
use App\Reports\Service\AttendanceReportService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ReportController
{
    public function __construct(private AttendanceReportService $service, private ResponseFactoryInterface $responseFactory)
    {
    }

    public function attendance(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $report = $this->service->generate($request->getAttribute(CurrentUser::class), $request->getQueryParams());
            $response = $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', "attachment; filename=\"attendance-{$report['group_id']}-{$report['start_date']}.pdf\"");
            $response->getBody()->write($report['bytes']);
            return $response;
        } catch (\DomainException $exception) {
            return \App\Shared\JsonResponse::error($this->responseFactory, 404, $exception->getMessage());
        } catch (\InvalidArgumentException $exception) {
            return \App\Shared\JsonResponse::error($this->responseFactory, 400, $exception->getMessage());
        } catch (\Throwable) {
            return \App\Shared\JsonResponse::error($this->responseFactory, 500, 'Unexpected server error.');
        }
    }
}
