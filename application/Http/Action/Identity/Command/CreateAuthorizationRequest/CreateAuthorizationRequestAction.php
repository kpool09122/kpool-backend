<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreateAuthorizationRequest;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\BadRequestHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest\CreateAuthorizationRequestInput;
use Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest\CreateAuthorizationRequestInterface;
use Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest\CreateAuthorizationRequestOutput;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Throwable;
use ValueError;

readonly class CreateAuthorizationRequestAction
{
    public function __construct(private CreateAuthorizationRequestInterface $useCase, private LoggerInterface $logger, private ActorContext $actorContext)
    {
    }

    public function __invoke(CreateAuthorizationRequestRequest $request): JsonResponse
    {
        try {
            try {
                $output = new CreateAuthorizationRequestOutput();
                $input = new CreateAuthorizationRequestInput(
                    $this->actorContext->identityIdentifier,
                    new OAuthClientIdentifier($request->clientIdentifier()),
                    $request->redirectUri(),
                    array_map(static fn (string $scope): AuthorizationScope => AuthorizationScope::from($scope), $request->scopes()),
                    $request->resource(),
                    $request->codeChallenge(),
                    $request->state(),
                );
                $this->useCase->process($input, $output);
                $result = $output->toArray();
            } catch (InvalidAuthorizationRequestException $exception) {
                throw new BadRequestHttpException(detail: error_message('oauth_invalid_request', $request->language()), previous: $exception);
            } catch (InvalidArgumentException|ValueError) {
                throw new BadRequestHttpException(detail: error_message('oauth_invalid_request', $request->language()));
            }
        } catch (BadRequestHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus(), ['Cache-Control' => 'no-store']);
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(previous: $exception);
        }

        return response()->json($result, 201)->header('Cache-Control', 'no-store');
    }
}
