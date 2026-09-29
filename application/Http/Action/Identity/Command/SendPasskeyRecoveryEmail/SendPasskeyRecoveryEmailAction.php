<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SendPasskeyRecoveryEmail;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail\SendPasskeyRecoveryEmailInput;
use Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail\SendPasskeyRecoveryEmailInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SendPasskeyRecoveryEmailAction
{
    public function __construct(
        private SendPasskeyRecoveryEmailInterface $useCase,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendPasskeyRecoveryEmailRequest $request): Response
    {
        try {
            try {
                $input = new SendPasskeyRecoveryEmailInput(
                    new Email($request->email()),
                    Language::from($request->language()),
                );
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            $this->useCase->process($input);
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

        return response()->noContent();
    }
}
