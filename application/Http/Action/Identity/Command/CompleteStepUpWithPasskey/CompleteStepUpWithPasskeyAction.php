<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CompleteStepUpWithPasskey;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskeyInput;
use Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskeyInterface;
use Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskeyOutput;
use Source\Identity\Domain\Exception\ChallengeSessionIdentityMismatchException;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionPurposeMismatchException;
use Source\Identity\Domain\Exception\PasskeyAuthenticationFailedException;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CompleteStepUpWithPasskeyAction
{
    public function __construct(private CompleteStepUpWithPasskeyInterface $useCase, private ActorContext $actorContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(CompleteStepUpWithPasskeyRequest $request): JsonResponse
    {
        try {
            try {
                $input = new CompleteStepUpWithPasskeyInput($this->actorContext->identityIdentifier, new ChallengeSessionKey($request->challengeKey()), new WebAuthnCredentialId($request->credentialId()), json_encode($request->credential(), JSON_THROW_ON_ERROR));
                $output = new CompleteStepUpWithPasskeyOutput();
            } catch (InvalidArgumentException|JsonException $e) {
                throw new UnprocessableEntityHttpException(detail:$e->getMessage(), previous:$e);
            }
            DB::beginTransaction();

            try {
                $this->useCase->process($input, $output);
                DB::commit();
            } catch (ChallengeSessionIdentityMismatchException|ChallengeSessionNotFoundException|ChallengeSessionPurposeMismatchException|PasskeyAuthenticationFailedException|WebAuthnVerificationException $e) {
                DB::rollBack();

                throw new UnauthorizedHttpException(detail:'Passkey step-up authentication failed.', previous:$e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnauthorizedHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string)$e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string)$e);

            throw new InternalServerErrorHttpException(detail:$e->getMessage(), previous:$e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
