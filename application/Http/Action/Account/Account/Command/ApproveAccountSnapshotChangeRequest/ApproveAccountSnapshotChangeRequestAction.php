<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\ApproveAccountSnapshotChangeRequest;

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
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestNotFoundException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest\ApproveAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest\ApproveAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest\ApproveAccountSnapshotChangeRequestOutput;
use Source\Account\Account\Domain\Exception\InvalidAccountSnapshotChangeRequestApprovalException;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveAccountSnapshotChangeRequestAction
{
    public function __construct(private ApproveAccountSnapshotChangeRequestInterface $useCase, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(ApproveAccountSnapshotChangeRequestRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new ApproveAccountSnapshotChangeRequestInput(new AccountSnapshotChangeRequestIdentifier($request->requestId()), $this->accountContext->principal());
                $output = new ApproveAccountSnapshotChangeRequestOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('disallowed', $language), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->useCase->process($input, $output);
                DB::commit();
            } catch (AccountSnapshotChangeRequestNotFoundException|AccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('account_snapshot_change_request_not_found', $language), previous: $e);
            } catch (AccountSnapshotChangeRequestForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (InvalidAccountSnapshotChangeRequestApprovalException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_account_snapshot_change_request_approval', $language), previous: $e);
            } catch (DocumentStorageFailedException $e) {
                DB::rollBack();

                throw new InternalServerErrorHttpException(previous: $e);
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
