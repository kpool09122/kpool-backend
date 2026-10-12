<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Principal\Query\ListPrincipalGroups;

use Application\Http\Context\AccountContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListPrincipalGroupsAction
{
    public function __construct(private ListPrincipalGroupsInterface $listPrincipalGroups, private AccountContext $accountContext, private SiteManagementContext $siteManagementContext, private LoggerInterface $logger)
    {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(ListPrincipalGroupsRequest $request): JsonResponse
    {
        try {
            try {
                $result = $this->listPrincipalGroups->process(new ListPrincipalGroupsInput($this->accountContext->principal()->accountIdentifier(), $this->siteManagementContext->principalIdentifier));
            } catch (UnauthorizedException $e) {
                throw new ForbiddenHttpException(detail: error_message('unauthorized', $request->language()), previous: $e);
            }

            return response()->json($result->toArray(), Response::HTTP_OK);
        } catch (ForbiddenHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $request->language()), previous: $e);
        }
    }
}
