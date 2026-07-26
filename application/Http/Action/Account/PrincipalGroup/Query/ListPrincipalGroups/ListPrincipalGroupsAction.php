<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\PrincipalGroup\Query\ListPrincipalGroups;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Source\Account\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Account\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListPrincipalGroupsAction
{
    public function __construct(
        private ListPrincipalGroupsInterface $listPrincipalGroups,
        private AccountContext $accountContext,
        // 防御的catch内でのみ利用され、PHPStanのchecked exception解析では未到達扱いになるため。
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(ListPrincipalGroupsRequest $request): JsonResponse
    {
        try {
            $principalGroups = $this->listPrincipalGroups->process(new ListPrincipalGroupsInput($this->accountContext->principal()->accountIdentifier(), $this->accountContext->principal()));
        } catch (AccountUpdateForbiddenException $e) {
            $exception = new ForbiddenHttpException(detail: error_message('account_update_forbidden', $request->language()), previous: $e);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json(['principalGroups' => array_map(static fn (PrincipalGroupReadModel $principalGroup): array => $principalGroup->toArray(), $principalGroups)], Response::HTTP_OK);
    }
}
