<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Application\Http\Context\ActorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
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
        if ($e instanceof ValidationException) {
            $this->logHandledException($e, $request);

            $httpException = new UnprocessableEntityHttpException(
                detail: $e->validator->errors()->first() ?: $e->getMessage(),
                extensions: ['errors' => $e->errors()],
                previous: $e
            );

            return response()->json($httpException->toProblemDetails(), $httpException->getHttpStatus());
        }

        if ($e instanceof PrincipalNotFoundException) {
            $this->logHandledException($e, $request);

            $language = $this->resolveLanguage($request);
            $httpException = new NotFoundHttpException(
                detail: error_message('principal_not_found', $language),
                previous: $e
            );

            return response()->json($httpException->toProblemDetails(), $httpException->getHttpStatus());
        }

        if ($e instanceof HttpException && $e->getHttpStatus() < 500) {
            $this->logHandledException($e, $request);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        }

        $this->logServerException($e, $request);
        $language = $this->resolveLanguage($request);

        return response()->json([
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'title' => 'Internal Server Error',
            'detail' => error_message('internal_server_error', $language),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function renderView(Throwable $e): HttpResponse
    {
        $statusCode = $e instanceof HttpException ? $e->getHttpStatus() : Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            request() instanceof Request
                ? $this->logServerException($e, request())
                : Log::error('Unhandled server exception.', [
                    'exception' => $e,
                    'exception_class' => $e::class,
                    'message' => $e->getMessage(),
                ]);
        }

        return response()->view('errors.exception', ['exception' => $e], $statusCode);
    }

    private function resolveLanguage(Request $request): string
    {
        $actorContext = app()->bound(ActorContext::class) ? app(ActorContext::class) : null;

        return $actorContext?->language->value ?? $request->header('Accept-Language', 'en');
    }

    private function logServerException(Throwable $e, Request $request): void
    {
        Log::error('Unhandled server exception.', [
            ...$this->logContext($e, $request),
        ]);
    }

    private function logHandledException(Throwable $e, Request $request): void
    {
        Log::warning('Handled HTTP exception.', [
            ...$this->logContext($e, $request),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function logContext(Throwable $e, Request $request): array
    {
        $route = $request->route();

        return [
            'exception' => $e,
            'exception_class' => $e::class,
            'message' => $e->getMessage(),
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
            'route' => is_object($route) && method_exists($route, 'getName')
                ? ($route->getName() ?? $request->path())
                : $request->path(),
        ];
    }
}
