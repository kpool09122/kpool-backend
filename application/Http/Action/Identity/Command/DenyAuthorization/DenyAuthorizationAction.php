<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\DenyAuthorization;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\BadRequestHttpException;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Application\UseCase\Command\DenyAuthorization\DenyAuthorizationInput;
use Source\Identity\Application\UseCase\Command\DenyAuthorization\DenyAuthorizationInterface;
use Source\Identity\Application\UseCase\Command\DenyAuthorization\DenyAuthorizationOutput;
use Throwable;
use ValueError;

readonly class DenyAuthorizationAction
{
    public function __construct(private DenyAuthorizationInterface $useCase, private LoggerInterface $logger, private ActorContext $actorContext)
    {
    }

    public function __invoke(string $requestIdentifier): JsonResponse
    {
        try {
            try {
                $output = new DenyAuthorizationOutput();
                $this->useCase->process(new DenyAuthorizationInput($requestIdentifier, $this->actorContext->identityIdentifier), $output);
                $result = $output->toArray();
            } catch (InvalidAuthorizationConsentException $exception) {
                throw new BadRequestHttpException(detail: error_message('oauth_invalid_request', $this->actorContext->language->value), previous: $exception);
            } catch (InvalidArgumentException|ValueError) {
                throw new BadRequestHttpException(detail: error_message('oauth_invalid_request', $this->actorContext->language->value));
            } catch (AuthorizationRequestUnavailableException $exception) {
                throw match ($exception->reason) {
                    'expired' => new ConflictHttpException(detail: error_message('oauth_request_expired', $this->actorContext->language->value), extensions: ['code' => 'oauth_request_expired'], previous: $exception),
                    'completed' => new ConflictHttpException(detail: error_message('oauth_request_completed', $this->actorContext->language->value), extensions: ['code' => 'oauth_request_completed'], previous: $exception),
                    default => new NotFoundHttpException(detail: error_message('oauth_invalid_request', $this->actorContext->language->value), previous: $exception),
                };
            }
        } catch (BadRequestHttpException|ConflictHttpException|NotFoundHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus(), ['Cache-Control' => 'no-store']);
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(previous: $exception);
        }

        return response()->json($result, 200)->header('Cache-Control', 'no-store');
    }
}
