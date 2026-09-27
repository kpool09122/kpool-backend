<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\GetSocialLinking;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSessionStorageServiceInterface;
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Throwable;

readonly class GetSocialLinkingAction
{
    public function __construct(
        private SocialLinkingSessionStorageServiceInterface $socialLinkingSessionStorageService,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(GetSocialLinkingRequest $request): JsonResponse
    {
        try {
            try {
                $session = $this->socialLinkingSessionStorageService->requireValid();
            } catch (SocialLinkingSessionInvalidException|SocialLinkingVerificationFailedException|InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(
                    detail: error_message('invalid_social_linking', $request->language()),
                    previous: $exception,
                );
            }
        } catch (UnprocessableEntityHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(
                detail: error_message('internal_server_error', $request->language()),
                previous: $exception,
            );
        }

        return response()->json([
            'provider' => $session->connection->provider()->value,
            'email' => (string) $session->email,
            'expiresAt' => $session->expiresAt->format(DATE_ATOM),
        ])->header('Cache-Control', 'no-store');
    }
}
