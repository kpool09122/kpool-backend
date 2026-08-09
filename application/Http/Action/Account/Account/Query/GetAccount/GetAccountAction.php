<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccount;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Application\UseCase\Query\GetAccount\GetAccountInput;
use Source\Account\Account\Application\UseCase\Query\GetAccount\GetAccountInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetAccountAction
{
    public function __construct(
        private GetAccountInterface $getAccount,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param GetAccountRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(GetAccountRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetAccountInput(
                    accountIdentifier: new AccountIdentifier($request->accountId()),
                    principal: $this->accountContext->principal(),
                    accountType: $this->accountContext->accountType(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $account = $this->getAccount->process($input);
            } catch (AccountNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('account_not_found', $language), previous: $e);
            } catch (AccountUpdateForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('account_update_forbidden', $language), previous: $e);
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($account->toArray(), Response::HTTP_OK);
    }
}
