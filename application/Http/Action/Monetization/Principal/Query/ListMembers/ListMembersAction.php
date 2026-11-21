<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Principal\Query\ListMembers;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\MonetizationContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInput;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInterface;
use Source\Monetization\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListMembersAction
{
    public function __construct(
        private ListMembersInterface $listMembers,
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
                $result = $this->listMembers->process(new ListMembersInput(
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

        return response()->json(['members' => array_map(static fn (PrincipalGroupMemberReadModel $row): array => $row->toArray(), $result)], Response::HTTP_OK);
    }
}
