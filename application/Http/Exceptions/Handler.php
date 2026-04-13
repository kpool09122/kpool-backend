<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Application\Http\Context\ActorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class Handler
{
    public function __invoke(Throwable $e, Request $request): Response
    {
        return $this->render($e, $request);
    }

    public static function shouldReportToSentry(Throwable $e): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        $dsn = config('sentry.dsn');

        if (! is_string($dsn) || trim($dsn) === '') {
            return false;
        }

        if ($e instanceof HttpException) {
            return $e->getHttpStatus() >= Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return true;
    }

    private function render(Throwable $e, Request $request): Response
    {
        if ($request->expectsJson()) {
            return $this->renderJson($e, $request);
        }

        return $this->renderView($e);
    }

    private function renderJson(Throwable $e, Request $request): JsonResponse
    {
        if ($e instanceof HttpException && $e->getHttpStatus() < 500) {
            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        }

        $actorContext = app()->bound(ActorContext::class) ? app(ActorContext::class) : null;
        $language = $actorContext?->language->value ?? $request->header('Accept-Language', 'en');

        return response()->json([
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'title' => 'Internal Server Error',
            'detail' => error_message('internal_server_error', $language),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function renderView(Throwable $e): HttpResponse
    {
        $statusCode = $e instanceof HttpException ? $e->getHttpStatus() : Response::HTTP_INTERNAL_SERVER_ERROR;

        return response()->view('errors.exception', ['exception' => $e], $statusCode);
    }
}
