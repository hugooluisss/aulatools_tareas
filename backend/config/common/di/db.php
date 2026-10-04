<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use App\Enrollments\Service\TaskDeliveryEnrollmentHook;
use App\Auth\Service\PasswordHasher;
use App\Auth\Service\PasswordHasherInterface;
use App\Auth\Middleware\RoleRestrictionMiddleware;
use App\Tasks\Service\TaskDeliveryEnrollmentHookAdapter;
use App\Shared\RouteParametersResolver;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use App\Reports\Renderer\PdfReportRenderer;
use App\Reports\Renderer\ReportRenderer;

return [
    ConnectionInterface::class => static fn () => (require dirname(__DIR__) . '/../db.php')(),
    PasswordHasherInterface::class => PasswordHasher::class,
    ParametersResolverInterface::class => RouteParametersResolver::class,
    ReportRenderer::class => PdfReportRenderer::class,
    RoleRestrictionMiddleware::class => static fn (ResponseFactoryInterface $responseFactory) => new RoleRestrictionMiddleware(
        ['admin'],
        $responseFactory,
    ),
    TaskDeliveryEnrollmentHook::class => static fn (TaskDeliveryEnrollmentHookAdapter $adapter) => $adapter,
];
