<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\ApproveAccountCategoryChangeRequest;

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
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestApprovalException;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveAccountCategoryChangeRequestAction
{
    public function __construct(private ApproveAccountCategoryChangeRequestInterface $useCase, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(ApproveAccountCategoryChangeRequestRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new ApproveAccountCategoryChangeRequestInput(new AccountCategoryChangeRequestIdentifier($request->requestId()), $this->accountContext->principal());
                $output = new ApproveAccountCategoryChangeRequestOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->useCase->process($input, $output);
                DB::commit();
            } catch (AccountCategoryChangeRequestNotFoundException|AccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountCategoryChangeRequestForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (InvalidAccountCategoryChangeRequestApprovalException $e) {
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
