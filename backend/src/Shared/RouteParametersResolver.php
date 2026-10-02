<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;
use Yiisoft\Router\CurrentRoute;

final class RouteParametersResolver implements ParametersResolverInterface
{
    public function __construct(private CurrentRoute $currentRoute)
    {
    }

    public function resolve(array $parameters, ServerRequestInterface $request): array
    {
        $arguments = array_intersect_key($this->currentRoute->getArguments(), $parameters);
        foreach ($arguments as $name => $value) {
            if ($parameters[$name]->getType()?->getName() === 'int') {
                $arguments[$name] = (int) $value;
            }
        }
        return $arguments;
    }
}
