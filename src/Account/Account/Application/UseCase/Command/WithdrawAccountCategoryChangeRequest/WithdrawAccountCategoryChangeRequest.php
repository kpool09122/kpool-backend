<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest;

use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;

readonly class WithdrawAccountCategoryChangeRequest implements WithdrawAccountCategoryChangeRequestInterface
{
    public function __construct(private AccountCategoryChangeRequestRepositoryInterface $accountCategoryChangeRequestRepository)
    {
    }

    public function process(WithdrawAccountCategoryChangeRequestInputPort $input, WithdrawAccountCategoryChangeRequestOutputPort $output): void
    {
        if ((string) $input->principal()->accountIdentifier() !== (string) $input->accountIdentifier()) {
            throw new AccountCategoryChangeRequestForbiddenException();
        }
        $request = $this->accountCategoryChangeRequestRepository->findById($input->requestIdentifier());
        if ($request === null) {
            throw new AccountCategoryChangeRequestNotFoundException();
        }
        if ((string) $request->accountIdentifier() !== (string) $input->accountIdentifier()) {
            throw new AccountCategoryChangeRequestForbiddenException();
        }

        $request->withdraw();
        $this->accountCategoryChangeRequestRepository->save($request);
        $output->setRequest($request);
    }
}
