<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\DeletePasskey;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Exception\CannotDeleteLastAuthenticationMethodException;
use Source\Identity\Application\UseCase\Command\DeletePasskey\DeletePasskeyInput;
use Source\Identity\Application\UseCase\Command\DeletePasskey\DeletePasskeyInterface;
use Source\Identity\Application\UseCase\Command\DeletePasskey\DeletePasskeyOutput;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\PasskeyCredentialNotFoundException;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DeletePasskeyAction
{
    public function __construct(
        private DeletePasskeyInterface $deletePasskey,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeletePasskeyRequest $request): JsonResponse
    {
        try {
            try {
                $input = new DeletePasskeyInput(
                    $this->actorContext->identityIdentifier,
                    new PasskeyCredentialIdentifier($request->passkeyIdentifier()),
                );
                $output = new DeletePasskeyOutput();
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            DB::beginTransaction();

            try {
                $this->deletePasskey->process($input, $output);
                DB::commit();
            } catch (PasskeyCredentialNotFoundException $exception) {
                DB::rollBack();

                throw new NotFoundHttpException(
                    detail: error_message('passkey_credential_not_found', $request->language()),
                    previous: $exception,
                );
            } catch (IdentityNotFoundException $exception) {
                DB::rollBack();

                throw new NotFoundHttpException(
                    detail: error_message('identity_not_found', $request->language()),
                    previous: $exception,
                );
            } catch (CannotDeleteLastAuthenticationMethodException $exception) {
                DB::rollBack();

                throw new ConflictHttpException(
                    detail: error_message('cannot_delete_last_authentication_method', $request->language()),
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
        } catch (ConflictHttpException|NotFoundHttpException|UnauthorizedHttpException|UnprocessableEntityHttpException $exception) {
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
