<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Closure;
use Google\Auth\AccessToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class EnsureCloudTaskAuthenticated
{
    public function __construct(private AccessToken $tokens)
    {
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $handler = config('queue.connections.cloudtasks.handler');
        $email = config('queue.connections.cloudtasks.service_account_email');
        if (! is_string($handler) || ! str_starts_with($handler, 'https://') || ! is_string($email) || $email === '') {
            return response()->json(['message' => 'Task handler is not configured.'], 503);
        }

        $token = $request->bearerToken();
        if ($token === null || $token === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $audience = rtrim($handler, '/');
        $path = '/' . config('cloud-tasks.uri');
        if (! str_ends_with($audience, $path)) {
            $audience .= $path;
        }
        $claims = $this->tokens->verify($token, [
            'audience' => $audience,
            'issuer' => 'https://accounts.google.com',
        ]);
        if ($claims === false || ($claims['email'] ?? null) !== $email || ($claims['email_verified'] ?? false) !== true) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
