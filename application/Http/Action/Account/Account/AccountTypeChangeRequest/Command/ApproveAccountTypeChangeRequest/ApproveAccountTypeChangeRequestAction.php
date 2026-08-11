<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\AccountTypeChangeRequest\Command\ApproveAccountTypeChangeRequest;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest\ApproveAccountTypeChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest\ApproveAccountTypeChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest\ApproveAccountTypeChangeRequestOutput;
use Source\Account\Account\Domain\Exception\InvalidAccountTypeChangeRequestApprovalException;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveAccountTypeChangeRequestAction
{
    public function __construct(private ApproveAccountTypeChangeRequestInterface $useCase, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(ApproveAccountTypeChangeRequestRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new ApproveAccountTypeChangeRequestInput(new AccountTypeChangeRequestIdentifier($request->requestId()), $this->accountContext->principal());
                $output = new ApproveAccountTypeChangeRequestOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->useCase->process($input, $output);
                DB::commit();
            } catch (AccountTypeChangeRequestNotFoundException|AccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountTypeChangeRequestForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (InvalidAccountTypeChangeRequestApprovalException $e) {
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

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
