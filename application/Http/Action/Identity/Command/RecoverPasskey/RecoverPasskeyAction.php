<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\RecoverPasskey;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\RecoverPasskey\RecoverPasskeyInput;
use Source\Identity\Application\UseCase\Command\RecoverPasskey\RecoverPasskeyInterface;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionPurposeMismatchException;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidPasskeyBackupStateException;
use Source\Identity\Domain\Exception\PasskeyCredentialAlreadyExistsException;
use Source\Identity\Domain\Exception\PasskeyRecoverySessionInvalidException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RecoverPasskeyAction
{
    public function __construct(
        private RecoverPasskeyInterface $useCase,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RecoverPasskeyRequest $request): Response
    {
        try {
            try {
                $input = new RecoverPasskeyInput(
                    new PasskeyRecoveryKey($request->recoveryKey()),
                    new ChallengeSessionKey($request->challengeKey()),
                    new PasskeyDisplayName($request->displayName()),
                    json_encode($request->credential(), JSON_THROW_ON_ERROR),
                );
            } catch (InvalidArgumentException|JsonException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            DB::beginTransaction();

            try {
                $this->useCase->process($input);
                DB::commit();
            } catch (PasskeyCredentialAlreadyExistsException $exception) {
                DB::rollBack();

                throw new ConflictHttpException(
                    detail: error_message('passkey_credential_already_exists', $request->language()),
                    previous: $exception,
                );
            } catch (IdentityNotFoundException|PasskeyUserNotFoundException $exception) {
                DB::rollBack();

                throw new NotFoundHttpException(
                    detail: error_message('identity_not_found', $request->language()),
                    previous: $exception,
                );
            } catch (
                ChallengeSessionNotFoundException
                |ChallengeSessionPurposeMismatchException
                |InvalidPasskeyBackupStateException
                |PasskeyRecoverySessionInvalidException
                |WebAuthnVerificationException $exception
            ) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(
                    detail: error_message('invalid_passkey_registration', $request->language()),
                    previous: $exception,
                );
            } catch (Throwable $exception) {
                DB::rollBack();

                throw $exception;
            }
        } catch (ConflictHttpException|NotFoundHttpException|UnprocessableEntityHttpException $exception) {
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
