<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequiresApprovalException;
use Source\Account\Account\Application\Exception\AccountSnapshotForbiddenException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Service\AccountSnapshotDocumentServiceInterface;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;
use Source\Account\Account\Domain\Factory\AccountSnapshotFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidatorInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotProfile;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotStatus;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Throwable;

readonly class SaveAccountSnapshot implements SaveAccountSnapshotInterface
{
    public function __construct(private AccountRepositoryInterface $accountRepository, private AccountSnapshotChangeRequestRepositoryInterface $accountSnapshotChangeRequestRepository, private AccountSnapshotRepositoryInterface $accountSnapshotRepository, private AccountDocumentRequirementValidatorInterface $accountDocumentRequirementValidator, private AccountSnapshotDocumentServiceInterface $accountSnapshotDocumentService, private AccountSnapshotFactoryInterface $accountSnapshotFactory, private PolicyEvaluatorInterface $policyEvaluator, private AccountCategoryChangeRequestRepositoryInterface $accountCategoryChangeRequestRepository)
    {
    }

    public function process(SaveAccountSnapshotInputPort $input, SaveAccountSnapshotOutputPort $output): void
    {
        if ((string)$input->principal()->accountIdentifier() !== (string)$input->accountIdentifier()) {
            throw new AccountSnapshotForbiddenException();
        }
        $account = $this->accountRepository->findById($input->accountIdentifier()) ?? throw new AccountNotFoundException();
        if (! $this->policyEvaluator->evaluate($input->principal(), Action::UPDATE, Resource::account($account->accountIdentifier(), $account->type()))) {
            throw new AccountSnapshotForbiddenException();
        }
        if ($this->accountSnapshotChangeRequestRepository->findByAccountIdAndStatuses($account->accountIdentifier(), [AccountSnapshotChangeRequestStatus::PENDING]) !== []) {
            throw new AccountSnapshotForbiddenException();
        }

        if ($this->accountCategoryChangeRequestRepository->findByAccountIdAndStatuses($account->accountIdentifier(), [AccountCategoryChangeRequestStatus::PENDING]) !== []) {
            throw new AccountSnapshotForbiddenException();
        }

        if (! $account->hasRequiredContactForCategoryChange()) {
            throw new IncompleteAccountContactForCategoryChangeException();
        }
        $accountType = $account->type() ?? throw new AccountSetupUnavailableException();
        $this->accountDocumentRequirementValidator->validate($accountType, $account->documents()->documentTypes());
        $info = AccountSnapshotProfile::fromAccount($account);
        $hashes = $this->accountSnapshotDocumentService->hashes($account->documents()->all());
        $current = $this->accountSnapshotRepository->findCurrent($account->accountIdentifier());
        if ($current !== null && $current->hasSameContents($info, $hashes)) {
            $output->setSnapshot($current);

            return;
        }
        $approvedRequests = $this->accountCategoryChangeRequestRepository->findByAccountIdAndStatuses(
            $account->accountIdentifier(),
            [AccountCategoryChangeRequestStatus::APPROVED],
        );
        if ($approvedRequests !== []) {
            throw new AccountSnapshotChangeRequiresApprovalException();
        }
        $id = $this->accountSnapshotFactory->createIdentifier();
        $documents = $this->accountSnapshotDocumentService->capture($account->accountIdentifier(), $id, $account->documents()->all());

        try {
            $snapshot = $this->accountSnapshotFactory->create($id, $account->accountIdentifier(), AccountSnapshotStatus::APPROVED, $info, $documents);
            if (! $snapshot->hasSameContents($info, $hashes)) {
                throw new DocumentStorageFailedException('Source documents changed during snapshot capture.');
            }
            $this->accountSnapshotRepository->save($snapshot);
        } catch (Throwable $e) {
            $this->accountSnapshotDocumentService->delete($documents);

            throw $e;
        }
        $output->setSnapshot($snapshot);
    }
}
