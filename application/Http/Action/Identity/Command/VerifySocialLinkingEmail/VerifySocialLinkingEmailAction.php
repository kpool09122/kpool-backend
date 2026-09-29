<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\VerifySocialLinkingEmail;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailInput;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailInterface;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailOutput;
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Throwable;

readonly class VerifySocialLinkingEmailAction
{
    public function __construct(
        private VerifySocialLinkingEmailInterface $useCase,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(VerifySocialLinkingEmailRequest $request): JsonResponse
    {
        try {
            try {
                $input = new VerifySocialLinkingEmailInput(new AuthCode($request->authCode()));
                $output = new VerifySocialLinkingEmailOutput();
                DB::transaction(function () use ($input, $output): void {
                    $this->useCase->process($input, $output);
                });
            } catch (SocialLinkingSessionInvalidException|SocialLinkingVerificationFailedException|UniqueConstraintViolationException|InvalidArgumentException $exception) {
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

        return response()->json($output->toArray())->header('Cache-Control', 'no-store');
    }
}
