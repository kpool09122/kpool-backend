<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\VerifyPasskeyRecoveryEmail;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail\VerifyPasskeyRecoveryEmailInput;
use Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail\VerifyPasskeyRecoveryEmailInterface;
use Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail\VerifyPasskeyRecoveryEmailOutput;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class VerifyPasskeyRecoveryEmailAction
{
    public function __construct(
        private VerifyPasskeyRecoveryEmailInterface $useCase,
        // 防御的catch内でのみ利用され、PHPStanのchecked exception解析では未到達扱いになるため。
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(VerifyPasskeyRecoveryEmailRequest $request): JsonResponse
    {
        try {
            try {
                $input = new VerifyPasskeyRecoveryEmailInput(
                    new Email($request->email()),
                    new AuthCode($request->authCode()),
                );
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }
            $output = new VerifyPasskeyRecoveryEmailOutput();

            try {
                $this->useCase->process($input, $output);
            } catch (PasskeyRecoveryVerificationFailedException $exception) {
                throw new UnprocessableEntityHttpException(
                    detail: error_message('invalid_passkey_registration', $request->language()),
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

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
