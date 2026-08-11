<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountTypeChangeRequestRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class ApproveAccountTypeChangeRequest implements ApproveAccountTypeChangeRequestInterface
{
    public function __construct(
        private AccountTypeChangeRequestRepositoryInterface $requestRepository,
        private AccountRepositoryInterface $accountRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(ApproveAccountTypeChangeRequestInputPort $input, ApproveAccountTypeChangeRequestOutputPort $output): void
    {
        $request = $this->requestRepository->findById($input->requestIdentifier());
        if ($request === null) {
            throw new AccountTypeChangeRequestNotFoundException();
        }

        $reviewerAccountIdentifier = $input->principal()->accountIdentifier();
        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::ACCOUNT_TYPE_CHANGE_REQUEST_APPROVE,
            Resource::account($reviewerAccountIdentifier),
        )) {
            throw new AccountTypeChangeRequestForbiddenException();
        }

        $account = $this->accountRepository->findById($request->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException();
        }

        $request->approve($reviewerAccountIdentifier);
        $account->changeType($request->requestedAccountType());

        $this->accountRepository->save($account);
        $this->requestRepository->save($request);

        $output->setRequest($request);
    }
}
