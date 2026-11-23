<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\Exception\InvalidAccountCategoryChangeRequestException;
use Source\Account\Account\Application\Exception\SameAccountCategoryChangeRequestException;
use Source\Account\Account\Domain\Factory\AccountCategoryChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;

readonly class RequestAccountCategoryChange implements RequestAccountCategoryChangeInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountCategoryChangeRequestRepositoryInterface $accountCategoryChangeRequestRepository,
        private AccountCategoryChangeRequestFactoryInterface $requestFactory,
        private AccountSnapshotRepositoryInterface $accountSnapshotRepository,
    ) {
    }

    public function process(RequestAccountCategoryChangeInputPort $input, RequestAccountCategoryChangeOutputPort $output): void
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException();
        }
        if ((string) $input->principal()->accountIdentifier() !== (string) $account->accountIdentifier()) {
            throw new AccountCategoryChangeRequestForbiddenException();
        }
        $requests = $this->accountCategoryChangeRequestRepository->findByAccountIdAndStatuses(
            $input->accountIdentifier(),
            [AccountCategoryChangeRequestStatus::APPROVED, AccountCategoryChangeRequestStatus::PENDING],
        );
        if ($requests !== []) {
            throw new InvalidAccountCategoryChangeRequestException();
        }
        if ($account->accountCategory() === $input->requestedAccountCategory()) {
            throw new SameAccountCategoryChangeRequestException();
        }
        $snapshot = $this->accountSnapshotRepository->findCurrent($account->accountIdentifier()) ?? throw new AccountSnapshotNotFoundException();
        $request = $this->requestFactory->create($account->accountIdentifier(), $account->accountCategory(), $input->requestedAccountCategory(), $snapshot->snapshotIdentifier());
        $this->accountCategoryChangeRequestRepository->save($request);
        $output->setRequest($request);
    }
}
