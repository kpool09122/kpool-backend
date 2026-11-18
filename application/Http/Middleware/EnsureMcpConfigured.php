<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Mcp\McpMetadata;
use Closure;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class EnsureMcpConfigured
{
    public function __construct(private McpMetadata $mcpMetadata)
    {
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->mcpMetadata->assertConfigured();
        } catch (LogicException) {
            return new JsonResponse(['error' => 'temporarily_unavailable'], 503, ['Cache-Control' => 'no-store']);
        }

        return $next($request);
    }
}
