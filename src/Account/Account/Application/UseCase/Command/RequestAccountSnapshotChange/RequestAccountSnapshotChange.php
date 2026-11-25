<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange;

use DateTimeImmutable;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidAccountSnapshotChangeRequestException;
use Source\Account\Account\Application\Exception\NoAccountSnapshotChangesException;
use Source\Account\Account\Application\Service\AccountSnapshotDocumentServiceInterface;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;
use Source\Account\Account\Domain\Factory\AccountSnapshotChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidatorInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotProfile;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class RequestAccountSnapshotChange implements RequestAccountSnapshotChangeInterface
{
    public function __construct(private AccountRepositoryInterface $accountRepository, private AccountSnapshotRepositoryInterface $accountSnapshotRepository, private AccountDocumentRequirementValidatorInterface $accountDocumentRequirementValidator, private AccountSnapshotChangeRequestFactoryInterface $accountSnapshotChangeRequestFactory, private PolicyEvaluatorInterface $policyEvaluator, private AccountSnapshotChangeRequestRepositoryInterface $accountSnapshotChangeRequestRepository, private AccountSnapshotDocumentServiceInterface $accountSnapshotDocumentService, private AccountCategoryChangeRequestRepositoryInterface $accountCategoryChangeRequestRepository)
    {
    }

    public function process(RequestAccountSnapshotChangeInputPort $input, RequestAccountSnapshotChangeOutputPort $output): void
    {
        if ((string)$input->principal()->accountIdentifier() !== (string)$input->accountIdentifier()) {
            throw new AccountSnapshotChangeRequestForbiddenException();
        }
        $account = $this->accountRepository->findById($input->accountIdentifier()) ?? throw new AccountNotFoundException();
        if (! $this->policyEvaluator->evaluate($input->principal(), Action::UPDATE, Resource::account($account->accountIdentifier(), $account->type()))) {
            throw new AccountSnapshotChangeRequestForbiddenException();
        }
        if ($this->accountCategoryChangeRequestRepository->findByAccountIdAndStatuses($account->accountIdentifier(), [AccountCategoryChangeRequestStatus::PENDING]) !== []) {
            throw new AccountSnapshotChangeRequestForbiddenException();
        }
        if (! $account->hasRequiredContactForCategoryChange()) {
            throw new IncompleteAccountContactForCategoryChangeException();
        }
        $accountType = $account->type() ?? throw new AccountSetupUnavailableException();
        $this->accountDocumentRequirementValidator->validate($accountType, $account->documents()->documentTypes());
        $current = $this->accountSnapshotRepository->findCurrent($account->accountIdentifier());
        if ($current === null) {
            throw new AccountSnapshotNotFoundException();
        }
        if ($this->accountSnapshotChangeRequestRepository->findByAccountIdAndStatuses($account->accountIdentifier(), [AccountSnapshotChangeRequestStatus::PENDING]) !== []) {
            throw new InvalidAccountSnapshotChangeRequestException();
        }
        $profile = AccountSnapshotProfile::fromAccount($account);
        $hashes = $this->accountSnapshotDocumentService->hashes($account->documents()->all());
        if ($current->hasSameContents($profile, $hashes)) {
            throw new NoAccountSnapshotChangesException();
        }
        $id = $this->accountSnapshotChangeRequestFactory->createIdentifier();
        $request = $this->accountSnapshotChangeRequestFactory->create($id, $account->accountIdentifier(), $current->snapshotIdentifier(), $input->changeReason(), new DateTimeImmutable());
        $this->accountSnapshotChangeRequestRepository->save($request);
        $output->setRequest($request);
    }
}
