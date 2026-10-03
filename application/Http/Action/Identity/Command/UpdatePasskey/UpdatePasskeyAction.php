<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\UpdatePasskey;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyInput;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyInterface;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyOutput;
use Source\Identity\Domain\Exception\PasskeyCredentialNotFoundException;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class UpdatePasskeyAction
{
    public function __construct(
        private UpdatePasskeyInterface $updatePasskey,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdatePasskeyRequest $request): JsonResponse
    {
        try {
            try {
                $input = new UpdatePasskeyInput(
                    $this->actorContext->identityIdentifier,
                    new PasskeyCredentialIdentifier($request->passkeyIdentifier()),
                    new PasskeyDisplayName($request->displayName()),
                );
                $output = new UpdatePasskeyOutput();
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            DB::beginTransaction();

            try {
                $this->updatePasskey->process($input, $output);
                DB::commit();
            } catch (PasskeyCredentialNotFoundException $exception) {
                DB::rollBack();

                throw new NotFoundHttpException(
                    detail: error_message('passkey_credential_not_found', $request->language()),
                    previous: $exception,
                );
            } catch (StepUpAuthenticationRequiredException $exception) {
                DB::rollBack();

                throw new UnauthorizedHttpException(
                    detail: 'Recent passkey management authentication is required.',
                    previous: $exception,
                );
            } catch (Throwable $exception) {
                DB::rollBack();

                throw $exception;
            }
        } catch (NotFoundHttpException|UnauthorizedHttpException|UnprocessableEntityHttpException $exception) {
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
