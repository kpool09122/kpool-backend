<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\AddPasskey;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskeyInput;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskeyInterface;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskeyOutput;
use Source\Identity\Domain\Exception\ChallengeSessionIdentityMismatchException;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionPurposeMismatchException;
use Source\Identity\Domain\Exception\InvalidPasskeyBackupStateException;
use Source\Identity\Domain\Exception\PasskeyCredentialAlreadyExistsException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class AddPasskeyAction
{
    public function __construct(
        private AddPasskeyInterface $addPasskey,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AddPasskeyRequest $request): JsonResponse
    {
        try {
            try {
                $input = new AddPasskeyInput(
                    $this->actorContext->identityIdentifier,
                    new ChallengeSessionKey($request->challengeKey()),
                    new PasskeyDisplayName($request->displayName()),
                    json_encode($request->credential(), JSON_THROW_ON_ERROR),
                );
                $output = new AddPasskeyOutput();
            } catch (InvalidArgumentException|JsonException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            DB::beginTransaction();

            try {
                $this->addPasskey->process($input, $output);
                DB::commit();
            } catch (PasskeyCredentialAlreadyExistsException $exception) {
                DB::rollBack();

                throw new ConflictHttpException(
                    detail: error_message('passkey_credential_already_exists', $request->language()),
                    previous: $exception,
                );
            } catch (PasskeyUserNotFoundException $exception) {
                DB::rollBack();

                throw new NotFoundHttpException(
                    detail: error_message('passkey_user_not_found', $request->language()),
                    previous: $exception,
                );
            } catch (
                ChallengeSessionIdentityMismatchException
                |ChallengeSessionNotFoundException
                |ChallengeSessionPurposeMismatchException
                |InvalidPasskeyBackupStateException
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

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
