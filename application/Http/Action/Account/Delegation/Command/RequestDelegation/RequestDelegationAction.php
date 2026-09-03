<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\RequestDelegation;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\AccountDelegation\Application\Exception\AccountDelegationForbiddenException;
use Source\Account\AccountDelegation\Application\Exception\AccountDelegationUnavailableException;
use Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation\RequestAccountDelegationInput;
use Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation\RequestAccountDelegationInterface;
use Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation\RequestAccountDelegationOutput;
use Source\Account\AccountDelegation\Domain\Exception\AccountDelegationAlreadyExistsException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RequestDelegationAction
{
    public function __construct(
        private RequestAccountDelegationInterface $requestDelegation,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RequestDelegationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RequestAccountDelegationInput(
                    $this->accountContext->principal(),
                    new AccountIdentifier($request->targetAccountIdentifier()),
                );
                $output = new RequestAccountDelegationOutput();
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            DB::beginTransaction();

            try {
                $this->requestDelegation->process($input, $output);
                DB::commit();
            } catch (AccountDelegationForbiddenException $exception) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed_delegation_operation', $request->language()), previous: $exception);
            } catch (AccountDelegationUnavailableException $exception) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('disallowed_delegation_operation', $request->language()), previous: $exception);
            } catch (AccountDelegationAlreadyExistsException $exception) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('account_delegation_already_exists', $request->language()), previous: $exception);
            } catch (Throwable $exception) {
                DB::rollBack();

                throw $exception;
            }
        } catch (ConflictHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: $exception->getMessage(), previous: $exception);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
