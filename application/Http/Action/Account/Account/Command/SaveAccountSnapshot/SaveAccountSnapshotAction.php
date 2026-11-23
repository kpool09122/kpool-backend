<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\SaveAccountSnapshot;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequiresApprovalException;
use Source\Account\Account\Application\Exception\AccountSnapshotForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotInput;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotInterface;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotOutput;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Throwable;
use ValueError;

readonly class SaveAccountSnapshotAction
{
    public function __construct(private SaveAccountSnapshotInterface $useCase, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(SaveAccountSnapshotRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new SaveAccountSnapshotInput(new AccountIdentifier($request->accountIdentifier()), $this->accountContext->principal());
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail:error_message('disallowed', $language), previous:$e);
            }

            try {
                $output = new SaveAccountSnapshotOutput();
                DB::transaction(fn () => $this->useCase->process($input, $output));

                return response()->json($output->toArray(), 200);

            } catch (DocumentStorageFailedException $e) {
                throw new InternalServerErrorHttpException(previous: $e);
            } catch (AccountSnapshotChangeRequiresApprovalException $e) {
                throw new ConflictHttpException(detail: error_message('account_snapshot_change_requires_approval', $language), previous: $e);
            } catch (AccountSnapshotForbiddenException $e) {
                throw new ForbiddenHttpException(detail:error_message('disallowed', $language), previous:$e);
            } catch (AccountSnapshotNotFoundException $e) {
                throw new NotFoundHttpException(detail:error_message('account_snapshot_not_found', $language), previous:$e);
            } catch (AccountNotFoundException $e) {
                throw new NotFoundHttpException(detail:error_message('account_not_found', $language), previous:$e);
            } catch (IncompleteAccountContactForCategoryChangeException|AccountSetupUnavailableException $e) {
                throw new UnprocessableEntityHttpException(detail:error_message('incomplete_account_snapshot', $language), previous:$e);
            } catch (InvalidDocumentsForVerificationException $e) {
                throw new UnprocessableEntityHttpException(detail:error_message('invalid_documents_for_category_change', $language), previous:$e);
            }
        } catch (ConflictHttpException|ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string)$e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string)$e);

            throw new InternalServerErrorHttpException(previous:$e);
        }
    }
}
