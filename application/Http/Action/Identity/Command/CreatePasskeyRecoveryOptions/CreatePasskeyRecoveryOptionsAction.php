<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreatePasskeyRecoveryOptions;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions\CreatePasskeyRecoveryOptionsInput;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions\CreatePasskeyRecoveryOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions\CreatePasskeyRecoveryOptionsOutput;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\PasskeyRecoverySessionInvalidException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreatePasskeyRecoveryOptionsAction
{
    public function __construct(
        private CreatePasskeyRecoveryOptionsInterface $useCase,
        // 防御的catch内でのみ利用され、PHPStanのchecked exception解析では未到達扱いになるため。
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreatePasskeyRecoveryOptionsRequest $request): JsonResponse
    {
        try {
            try {
                $input = new CreatePasskeyRecoveryOptionsInput(new PasskeyRecoveryKey($request->recoveryKey()));
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }
            $output = new CreatePasskeyRecoveryOptionsOutput();

            try {
                $this->useCase->process($input, $output);
            } catch (PasskeyRecoverySessionInvalidException $exception) {
                throw new UnprocessableEntityHttpException(
                    detail: error_message('invalid_passkey_registration', $request->language()),
                    previous: $exception,
                );
            } catch (IdentityNotFoundException|PasskeyUserNotFoundException $exception) {
                throw new NotFoundHttpException(
                    detail: error_message('identity_not_found', $request->language()),
                    previous: $exception,
                );
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (JsonException $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: $exception->getMessage(), previous: $exception);
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
