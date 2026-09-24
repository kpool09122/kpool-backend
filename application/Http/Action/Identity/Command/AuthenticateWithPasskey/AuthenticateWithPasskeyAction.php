<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\AuthenticateWithPasskey;

use Application\Http\Action\Identity\Support\IdentityResponsePayload;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyInput;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyInterface;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyOutput;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionPurposeMismatchException;
use Source\Identity\Domain\Exception\InvalidPasskeyBackupStateException;
use Source\Identity\Domain\Exception\PasskeyAuthenticationFailedException;
use Source\Identity\Domain\Exception\PasskeyBackupEligibilityChangedException;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class AuthenticateWithPasskeyAction
{
    public function __construct(
        private AuthenticateWithPasskeyInterface $authenticateWithPasskey,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AuthenticateWithPasskeyRequest $request): JsonResponse
    {
        try {
            try {
                $input = new AuthenticateWithPasskeyInput(
                    new ChallengeSessionKey($request->challengeKey()),
                    new WebAuthnCredentialId($request->credentialId()),
                    json_encode($request->credential(), JSON_THROW_ON_ERROR),
                );
                $output = new AuthenticateWithPasskeyOutput();
            } catch (InvalidArgumentException|JsonException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            DB::beginTransaction();

            try {
                $this->authenticateWithPasskey->process($input, $output);
                DB::commit();
            } catch (
                ChallengeSessionNotFoundException
                |ChallengeSessionPurposeMismatchException
                |InvalidPasskeyBackupStateException
                |PasskeyAuthenticationFailedException
                |PasskeyBackupEligibilityChangedException
                |WebAuthnVerificationException $exception
            ) {
                DB::rollBack();

                throw new UnauthorizedHttpException(
                    detail: error_message('invalid_passkey_authentication', $request->language()),
                    previous: $exception,
                );
            } catch (Throwable $exception) {
                DB::rollBack();

                throw $exception;
            }
        } catch (UnauthorizedHttpException|UnprocessableEntityHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: $exception->getMessage(), previous: $exception);
        }

        return response()->json(
            IdentityResponsePayload::normalizeProfileImage($output->toArray()),
            Response::HTTP_OK,
        );
    }
}
