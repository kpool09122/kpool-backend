<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\RevokeAuthorization;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Exception\AuthorizationConnectionNotFoundException;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationInput;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationInterface;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationOutput;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RevokeAuthorizationAction
{
    public function __construct(
        private RevokeAuthorizationInterface $revokeAuthorization,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RevokeAuthorizationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RevokeAuthorizationInput(
                    $this->actorContext->identityIdentifier,
                    new AuthorizationConnectionIdentifier($request->connectionIdentifier()),
                );
                $output = new RevokeAuthorizationOutput();
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: error_message('oauth_invalid_request', $request->language()), previous: $exception);
            }

            DB::beginTransaction();

            try {
                $this->revokeAuthorization->process($input, $output);
                DB::commit();
            } catch (AuthorizationConnectionNotFoundException $exception) {
                DB::rollBack();

                throw new NotFoundHttpException(
                    detail: error_message('authorization_connection_not_found', $request->language()),
                    previous: $exception,
                );
            } catch (Throwable $exception) {
                DB::rollBack();

                throw $exception;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $exception) {
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
