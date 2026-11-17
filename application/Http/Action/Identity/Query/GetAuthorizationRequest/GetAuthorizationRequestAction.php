<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\GetAuthorizationRequest;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\BadRequestHttpException;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInterface;
use Throwable;
use ValueError;

readonly class GetAuthorizationRequestAction
{
    public function __construct(private GetAuthorizationRequestInterface $useCase, private LoggerInterface $logger, private ActorContext $actorContext)
    {
    }

    public function __invoke(string $requestIdentifier): JsonResponse
    {
        try {
            try {
                $result = $this->useCase->process(new GetAuthorizationRequestInput($requestIdentifier, $this->actorContext->identityIdentifier))->toArray();
            } catch (InvalidAuthorizationRequestException $exception) {
                throw new BadRequestHttpException(detail: error_message('oauth_invalid_request', $this->actorContext->language->value), previous: $exception);
            } catch (InvalidArgumentException|ValueError) {
                throw new BadRequestHttpException(detail: error_message('oauth_invalid_request', $this->actorContext->language->value));
            } catch (AuthorizationRequestUnavailableException $exception) {
                throw match ($exception->reason) {
                    'expired' => new ConflictHttpException(detail: error_message('oauth_request_expired', $this->actorContext->language->value), extensions: ['code' => 'oauth_request_expired'], previous: $exception),
                    'completed' => new ConflictHttpException(detail: error_message('oauth_request_completed', $this->actorContext->language->value), extensions: ['code' => 'oauth_request_completed'], previous: $exception),
                    default => new NotFoundHttpException(detail: error_message('oauth_invalid_request', $this->actorContext->language->value), previous: $exception),
                };
            } catch (AuthorizationAccountUnavailableException $exception) {
                throw new ForbiddenHttpException(detail: error_message('oauth_account_unavailable', $this->actorContext->language->value), previous: $exception);
            }
        } catch (BadRequestHttpException|ConflictHttpException|NotFoundHttpException|ForbiddenHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus(), ['Cache-Control' => 'no-store']);
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(previous: $exception);
        }

        return response()->json($result, 200)->header('Cache-Control', 'no-store');
    }
}
