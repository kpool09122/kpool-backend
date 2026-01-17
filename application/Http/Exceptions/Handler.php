<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class Handler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Throwable $e, Request $request): Response
    {
        $this->report($e);

        return $this->render($e, $request);
    }

    private function report(Throwable $e): void
    {
        $this->logger->error((string)$e);

        // TODO: Sentry に記録する
        // Sentry::captureException($e);
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

        $language = (string)($request->input('language') ?? 'en');

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
