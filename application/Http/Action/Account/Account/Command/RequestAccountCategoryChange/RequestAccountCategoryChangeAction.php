<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\RequestAccountCategoryChange;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestAlreadyPendingException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Exception\SameAccountCategoryChangeRequestException;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInterface;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeOutput;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class RequestAccountCategoryChangeAction
{
    public function __construct(private RequestAccountCategoryChangeInterface $useCase, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(RequestAccountCategoryChangeRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new RequestAccountCategoryChangeInput(new AccountIdentifier($request->accountIdentifier()), $this->accountContext->principal(), AccountCategory::from($request->requestedAccountCategory()));
                $output = new RequestAccountCategoryChangeOutput();
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
            } catch (AccountCategoryChangeRequestForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (SameAccountCategoryChangeRequestException|AccountCategoryChangeRequestAlreadyPendingException|IncompleteAccountContactForCategoryChangeException|InvalidDocumentsForVerificationException $e) {
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
