<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Exceptions\UnauthorizedHttpException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            throw new UnauthorizedHttpException(
                detail: error_message('unauthorized', $request->header('Accept-Language', 'en')),
            );
        }

        return $next($request);
    }
}
