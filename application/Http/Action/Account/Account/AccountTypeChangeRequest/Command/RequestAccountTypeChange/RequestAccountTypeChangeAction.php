<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\AccountTypeChangeRequest\Command\RequestAccountTypeChange;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestAlreadyPendingException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Exception\SameAccountTypeChangeRequestException;
use Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange\RequestAccountTypeChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange\RequestAccountTypeChangeInterface;
use Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange\RequestAccountTypeChangeOutput;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class RequestAccountTypeChangeAction
{
    public function __construct(private RequestAccountTypeChangeInterface $useCase, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(RequestAccountTypeChangeRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new RequestAccountTypeChangeInput(new AccountIdentifier($request->accountIdentifier()), $this->accountContext->principal(), AccountType::from($request->requestedAccountType()));
                $output = new RequestAccountTypeChangeOutput();
            } catch (ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }
            DB::beginTransaction();

            try {
                $this->useCase->process($input, $output);
                DB::commit();
            } catch (AccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountTypeChangeRequestForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (SameAccountTypeChangeRequestException|AccountTypeChangeRequestAlreadyPendingException|InvalidDocumentsForVerificationException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException|NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
