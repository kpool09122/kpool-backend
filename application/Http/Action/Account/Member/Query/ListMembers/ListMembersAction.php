<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Member\Query\ListMembers;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\UseCase\Query\ListMembers\ListMembersInput;
use Source\Account\Principal\Application\UseCase\Query\ListMembers\ListMembersInterface;
use Source\Account\Principal\Application\UseCase\Query\MemberReadModel;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListMembersAction
{
    public function __construct(
        private ListMembersInterface $listMembers,
        private AccountContext $accountContext,
        // 防御的catch内でのみ利用され、PHPStanのchecked exception解析では未到達扱いになるため。
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(ListMembersRequest $request): JsonResponse
    {
        try {
            $members = $this->listMembers->process(new ListMembersInput($this->accountContext->principal()->accountIdentifier(), $this->accountContext->principal()));
        } catch (AccountUpdateForbiddenException $e) {
            $exception = new ForbiddenHttpException(detail: error_message('account_update_forbidden', $request->language()), previous: $e);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json(['members' => array_map(static fn (MemberReadModel $member): array => $member->toArray(), $members)], Response::HTTP_OK);
    }
}
