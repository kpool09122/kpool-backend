<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\GetCsrfToken;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetCsrfTokenAction
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $request->session()->token();

            return response()->noContent()->withHeaders(['Cache-Control' => 'no-store']);
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(
                detail: error_message('internal_server_error', $request->getPreferredLanguage() ?? 'en'),
                previous: $exception,
            );
        }
    }
}
