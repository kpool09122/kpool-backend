<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\RegisterWithPasskey;

use Application\Http\Action\Identity\Support\IdentityResponsePayload;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyInput;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyInterface;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyOutput;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionPurposeMismatchException;
use Source\Identity\Domain\Exception\InvalidPasskeyBackupStateException;
use Source\Identity\Domain\Exception\InvalidSignupInvitationException;
use Source\Identity\Domain\Exception\PasskeyCredentialAlreadyExistsException;
use Source\Identity\Domain\Exception\PasskeyUserAlreadyLinkedException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Domain\ValueObject\Language;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RegisterWithPasskeyAction
{
    public function __construct(
        private RegisterWithPasskeyInterface $registerWithPasskey,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RegisterWithPasskeyRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RegisterWithPasskeyInput(
                    new ChallengeSessionKey($request->challengeKey()),
                    new IdentityName($request->identityName()),
                    Language::from($request->language()),
                    new PasskeyDisplayName($request->displayName()),
                    json_encode($request->credential(), JSON_THROW_ON_ERROR),
                    $request->base64EncodedImage(),
                );
                $output = new RegisterWithPasskeyOutput();
            } catch (InvalidArgumentException|JsonException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            DB::beginTransaction();

            try {
                $this->registerWithPasskey->process($input, $output);
                DB::commit();
            } catch (AlreadyUserExistsException $exception) {
                DB::rollBack();

                throw new ConflictHttpException(
                    detail: error_message('already_user_exists', $request->language()),
                    previous: $exception,
                );
            } catch (PasskeyCredentialAlreadyExistsException $exception) {
                DB::rollBack();

                throw new ConflictHttpException(
                    detail: error_message('passkey_credential_already_exists', $request->language()),
                    previous: $exception,
                );
            } catch (PasskeyUserAlreadyLinkedException $exception) {
                DB::rollBack();

                throw new ConflictHttpException(
                    detail: error_message('invalid_passkey_registration', $request->language()),
                    previous: $exception,
                );
            } catch (PasskeyUserNotFoundException $exception) {
                DB::rollBack();

                throw new NotFoundHttpException(
                    detail: error_message('passkey_user_not_found', $request->language()),
                    previous: $exception,
                );
            } catch (InvalidBase64ImageException $exception) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(
                    detail: error_message('invalid_base64_image', $request->language()),
                    previous: $exception,
                );
            } catch (
                ChallengeSessionNotFoundException
                |ChallengeSessionPurposeMismatchException
                |InvalidPasskeyBackupStateException
                |InvalidSignupInvitationException
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

        return response()->json(
            IdentityResponsePayload::normalizeProfileImage($output->toArray()),
            Response::HTTP_CREATED,
        );
    }
}
