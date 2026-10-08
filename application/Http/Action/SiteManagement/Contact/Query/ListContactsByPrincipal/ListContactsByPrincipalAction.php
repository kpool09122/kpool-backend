<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\ListContactsByPrincipal;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal\ListContactsByPrincipalInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal\ListContactsByPrincipalInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal\ListContactsByPrincipalOutput;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListContactsByPrincipalAction
{
    public function __construct(
        private ListContactsByPrincipalInterface $listContactsByPrincipal,
        private ActorContext $actorContext,
        private SiteManagementContext $siteManagementContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ListContactsByPrincipalRequest $request): JsonResponse
    {
        try {
            $output = new ListContactsByPrincipalOutput();
            $this->listContactsByPrincipal->process(new ListContactsByPrincipalInput(
                $this->siteManagementContext->principalIdentifier,
                new PrincipalIdentifier($request->principalIdentifier()),
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
