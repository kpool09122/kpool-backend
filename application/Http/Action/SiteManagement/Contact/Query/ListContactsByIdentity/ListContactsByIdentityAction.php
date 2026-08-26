<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\ListContactsByIdentity;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityOutput;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListContactsByIdentityAction
{
    public function __construct(
        private ListContactsByIdentityInterface $listContactsByIdentity,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ListContactsByIdentityRequest $request): JsonResponse
    {
        try {
            $output = new ListContactsByIdentityOutput();
            $this->listContactsByIdentity->process(new ListContactsByIdentityInput(
                $this->actorContext->identityIdentifier,
                new IdentityIdentifier($request->identityIdentifier()),
            ), $output);
        } catch (UnauthorizedException $e) {
            $this->logger->error((string) $e);

            throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
