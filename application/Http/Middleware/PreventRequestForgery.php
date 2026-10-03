<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\ActorContext;
use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as LaravelPreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Override;
use Symfony\Component\HttpFoundation\Response;

class PreventRequestForgery extends LaravelPreventRequestForgery
{
    /** @param Request $request */
    #[Override]
    public function handle($request, Closure $next): Response
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException) {
            $actorContext = app()->bound(ActorContext::class) ? app(ActorContext::class) : null;
            $language = $actorContext?->language->value ?? $request->header('Accept-Language', 'en');

            return response()->json([
                'status' => 419,
                'title' => 'Page Expired',
                'detail' => error_message('csrf_token_mismatch', $language),
                'code' => 'csrf_token_mismatch',
            ], 419);
        }
    }
}
