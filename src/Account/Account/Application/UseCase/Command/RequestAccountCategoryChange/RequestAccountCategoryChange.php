<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestAlreadyPendingException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\SameAccountCategoryChangeRequestException;
use Source\Account\Account\Domain\Factory\AccountCategoryChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Service\DocumentRequirementValidatorInterface;
use Source\Account\Account\Domain\ValueObject\VerificationType;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;

readonly class RequestAccountCategoryChange implements RequestAccountCategoryChangeInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountCategoryChangeRequestRepositoryInterface $requestRepository,
        private AccountCategoryChangeRequestFactoryInterface $requestFactory,
        private DocumentRequirementValidatorInterface $documentRequirementValidator,
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
        if ($account->accountCategory() === $input->requestedAccountCategory()) {
            throw new SameAccountCategoryChangeRequestException();
        }
        $verificationType = $this->verificationTypeFor($input->requestedAccountCategory());
        if ($verificationType !== null) {
            $this->documentRequirementValidator->validate($verificationType, $account->documents()->documentTypes());
        }
        if ($this->requestRepository->findPendingByAccountId($input->accountIdentifier()) !== null) {
            throw new AccountCategoryChangeRequestAlreadyPendingException();
        }
        $request = $this->requestFactory->create($account->accountIdentifier(), $account->accountCategory(), $input->requestedAccountCategory());
        $this->requestRepository->save($request);
        $output->setRequest($request);
    }

    private function verificationTypeFor(AccountCategory $accountCategory): ?VerificationType
    {
        return match ($accountCategory) {
            AccountCategory::AGENCY => VerificationType::AGENCY,
            AccountCategory::TALENT => VerificationType::TALENT,
            AccountCategory::GENERAL => null,
        };
    }
}
