<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Principal\Query\ListPrincipalGroups;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\MonetizationContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Monetization\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListPrincipalGroupsAction
{
    public function __construct(
        private ListPrincipalGroupsInterface $listPrincipalGroups,
        private AccountContext $accountContext,
        private MonetizationContext $monetizationContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(): JsonResponse
    {
        try {
            try {
                $result = $this->listPrincipalGroups->process(new ListPrincipalGroupsInput(
                    $this->accountContext->principal()->accountIdentifier(),
                    $this->monetizationContext->principalIdentifier,
                ));
            } catch (UnauthorizedException $e) {
                throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
            }
        } catch (ForbiddenHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json(['principalGroups' => array_map(static fn (PrincipalGroupReadModel $row): array => $row->toArray(), $result)], Response::HTTP_OK);
    }
}
