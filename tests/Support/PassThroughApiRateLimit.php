<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PassThroughApiRateLimit
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $surface, string $operation): Response
    {
        return $next($request);
    }
}
