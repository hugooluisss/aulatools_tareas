<?php

declare(strict_types=1);

use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteCollectorInterface;

return [RouteCollectionInterface::class => static fn (RouteCollectorInterface $collector) => new RouteCollection($collector->addRoute(...require dirname(__DIR__) . '/routes.php'))];
