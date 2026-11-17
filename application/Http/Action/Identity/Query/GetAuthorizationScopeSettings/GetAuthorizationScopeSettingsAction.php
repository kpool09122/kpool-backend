<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\GetAuthorizationScopeSettings;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\GetAuthorizationScopeSettingsInput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\GetAuthorizationScopeSettingsInterface;
use Throwable;

readonly class GetAuthorizationScopeSettingsAction
{
    public function __construct(private GetAuthorizationScopeSettingsInterface $useCase, private ActorContext $actorContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $result = $this->useCase->process(new GetAuthorizationScopeSettingsInput($this->actorContext->identityIdentifier))->toArray();
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($result, 200)->header('Cache-Control', 'no-store');
    }
}
