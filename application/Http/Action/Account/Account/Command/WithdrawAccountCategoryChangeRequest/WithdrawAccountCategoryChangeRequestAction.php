<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\WithdrawAccountCategoryChangeRequest;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest\WithdrawAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest\WithdrawAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest\WithdrawAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestWithdrawalException;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class WithdrawAccountCategoryChangeRequestAction
{
    public function __construct(private WithdrawAccountCategoryChangeRequestInterface $withdrawAccountCategoryChangeRequest, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(WithdrawAccountCategoryChangeRequestRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new WithdrawAccountCategoryChangeRequestInput(
                    new AccountIdentifier($request->accountIdentifier()),
                    new AccountCategoryChangeRequestIdentifier($request->requestId()),
                    $this->accountContext->principal(),
                );
                $output = new WithdrawAccountCategoryChangeRequestOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->withdrawAccountCategoryChangeRequest->process($input, $output);
                DB::commit();
            } catch (AccountCategoryChangeRequestNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('account_category_change_request_not_found', $language), previous: $e);
            } catch (AccountCategoryChangeRequestForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (InvalidAccountCategoryChangeRequestWithdrawalException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_account_category_change_request_withdrawal', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException|NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
