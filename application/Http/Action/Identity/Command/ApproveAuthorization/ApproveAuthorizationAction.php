<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\ApproveAuthorization;

use Application\Http\Action\Support\RequestValue;
use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\BadRequestHttpException;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationInput;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationInterface;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationOutput;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Throwable;
use ValueError;

readonly class ApproveAuthorizationAction
{
    public function __construct(private ApproveAuthorizationInterface $useCase, private LoggerInterface $logger, private ActorContext $actorContext)
    {
    }

    public function __invoke(ApproveAuthorizationRequest $request, string $requestIdentifier): JsonResponse
    {
        try {
            try {
                $output = new ApproveAuthorizationOutput();
                $input = new ApproveAuthorizationInput(
                    $requestIdentifier,
                    $this->actorContext->identityIdentifier,
                    array_map(static fn (string $scope): AuthorizationScope => AuthorizationScope::from($scope), $request->scopes()),
                    array_map(static fn (array $target): AuthorizationTarget => new AuthorizationTarget(
                        new AccountIdentifier(RequestValue::string($target['accountIdentifier'])),
                        ($target['delegationIdentifier'] ?? null) === null ? null : new DelegationIdentifier(RequestValue::string($target['delegationIdentifier'])),
                    ), $request->targets()),
                    new AccountIdentifier($request->initialAccountIdentifier())
                );
                DB::transaction(fn () => $this->useCase->process($input, $output));
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
