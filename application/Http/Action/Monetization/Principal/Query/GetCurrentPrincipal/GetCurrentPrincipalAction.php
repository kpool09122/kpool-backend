<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Principal\Query\GetCurrentPrincipal;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Monetization\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInput;
use Source\Monetization\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Monetization\Shared\Domain\Exception\PrincipalNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetCurrentPrincipalAction
{
    public function __construct(
        private GetCurrentPrincipalInterface $getCurrentPrincipal,
        private ActorContext $actorContext,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(): JsonResponse
    {
        try {
            try {
                $input = new GetCurrentPrincipalInput(
                    $this->accountContext->originalIdentityIdentifier(),
                    $this->accountContext->principal()->accountIdentifier(),
                );
                $readModel = $this->getCurrentPrincipal->process($input);
            } catch (PrincipalNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('principal_not_found', $this->actorContext->language->value), previous: $e);
            }
        } catch (NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($readModel->toArray(), Response::HTTP_OK);
    }
}
