<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\UpdateAuthorizationScopeSettings;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\BadRequestHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsInput;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsInterface;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsOutput;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Throwable;
use ValueError;

readonly class UpdateAuthorizationScopeSettingsAction
{
    public function __construct(private UpdateAuthorizationScopeSettingsInterface $useCase, private ActorContext $actorContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(UpdateAuthorizationScopeSettingsRequest $request): JsonResponse
    {
        try {
            try {
                $output = new UpdateAuthorizationScopeSettingsOutput();
                $input = new UpdateAuthorizationScopeSettingsInput($this->actorContext->identityIdentifier, array_map(AuthorizationScope::from(...), $request->scopes()));
                DB::transaction(fn () => $this->useCase->process($input, $output));
                $result = $output->toArray();
            } catch (InvalidArgumentException|ValueError) {
                throw new BadRequestHttpException(detail: error_message('oauth_invalid_request', $this->actorContext->language->value));
            }
        } catch (BadRequestHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus(), ['Cache-Control' => 'no-store']);
        } catch (StepUpAuthenticationRequiredException $exception) {
            $httpException = new UnauthorizedHttpException(
                detail: error_message('recent_authentication_required', $this->actorContext->language->value),
                extensions: ['code' => 'recent_authentication_required'],
                previous: $exception,
            );

            return response()->json($httpException->toProblemDetails(), $httpException->getHttpStatus(), ['Cache-Control' => 'no-store']);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($result, 200)->header('Cache-Control', 'no-store');
    }
}
