<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestAlreadyPendingException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\SameAccountTypeChangeRequestException;
use Source\Account\Account\Domain\Factory\AccountTypeChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountTypeChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidatorInterface;

readonly class RequestAccountTypeChange implements RequestAccountTypeChangeInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountTypeChangeRequestRepositoryInterface $requestRepository,
        private AccountTypeChangeRequestFactoryInterface $requestFactory,
        private AccountDocumentRequirementValidatorInterface $documentRequirementValidator,
    ) {
    }

    public function process(RequestAccountTypeChangeInputPort $input, RequestAccountTypeChangeOutputPort $output): void
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException();
        }
        if ((string) $input->principal()->accountIdentifier() !== (string) $account->accountIdentifier()) {
            throw new AccountTypeChangeRequestForbiddenException();
        }
        if ($account->type() === $input->requestedAccountType()) {
            throw new SameAccountTypeChangeRequestException();
        }
        $this->documentRequirementValidator->validate($input->requestedAccountType(), $account->documents()->documentTypes());
        if ($this->requestRepository->existsPending($input->accountIdentifier())) {
            throw new AccountTypeChangeRequestAlreadyPendingException();
        }
        $request = $this->requestFactory->create($account->accountIdentifier(), $account->type(), $input->requestedAccountType());
        $this->requestRepository->save($request);
        $output->setRequest($request);
    }
}
