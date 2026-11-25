<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\RequestAccountSnapshotChange;

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
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidAccountSnapshotChangeRequestException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Exception\NoAccountSnapshotChangesException;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeInterface;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeOutput;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Throwable;
use ValueError;

readonly class RequestAccountSnapshotChangeAction
{
    public function __construct(private RequestAccountSnapshotChangeInterface $useCase, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(RequestAccountSnapshotChangeRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new RequestAccountSnapshotChangeInput(new AccountIdentifier($request->accountIdentifier()), $this->accountContext->principal(), new AccountSnapshotChangeReason($request->changeReason()));
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail:error_message('disallowed', $language), previous:$e);
            }

            try {
                $output = new RequestAccountSnapshotChangeOutput();
                DB::transaction(fn () => $this->useCase->process($input, $output));

                return response()->json($output->toArray(), 201);

            } catch (NoAccountSnapshotChangesException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('no_account_snapshot_changes', $language), previous: $e);
            } catch (InvalidAccountSnapshotChangeRequestException $e) {
                throw new ConflictHttpException(detail: error_message('invalid_account_snapshot_change_request', $language), previous: $e);
            } catch (AccountSnapshotChangeRequestForbiddenException $e) {
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
