<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest;

use DateTimeImmutable;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Event\AccountCategoryChanged;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class ApproveAccountCategoryChangeRequest implements ApproveAccountCategoryChangeRequestInterface
{
    public function __construct(
        private AccountCategoryChangeRequestRepositoryInterface $requestRepository,
        private AccountRepositoryInterface $accountRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(ApproveAccountCategoryChangeRequestInputPort $input, ApproveAccountCategoryChangeRequestOutputPort $output): void
    {
        $request = $this->requestRepository->findById($input->requestIdentifier());
        if ($request === null) {
            throw new AccountCategoryChangeRequestNotFoundException();
        }

        $reviewerAccountIdentifier = $input->principal()->accountIdentifier();
        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE,
            Resource::account($reviewerAccountIdentifier),
        )) {
            throw new AccountCategoryChangeRequestForbiddenException();
        }

        $account = $this->accountRepository->findById($request->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException();
        }

        $previousAccountCategory = $account->accountCategory();
        $newAccountCategory = $request->requestedAccountCategory();

        $request->approve($reviewerAccountIdentifier);
        $account->setAccountCategory($newAccountCategory);

        $this->accountRepository->save($account);
        $this->requestRepository->save($request);

        $this->eventDispatcher->dispatch(new AccountCategoryChanged(
            accountIdentifier: $account->accountIdentifier(),
            previousAccountCategory: $previousAccountCategory,
            newAccountCategory: $newAccountCategory,
            reviewerAccountIdentifier: $reviewerAccountIdentifier,
            changedAt: new DateTimeImmutable(),
        ));

        $output->setRequest($request);
    }
}
