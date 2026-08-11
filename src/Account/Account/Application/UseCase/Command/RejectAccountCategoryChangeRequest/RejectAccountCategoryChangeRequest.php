<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest;

use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class RejectAccountCategoryChangeRequest implements RejectAccountCategoryChangeRequestInterface
{
    public function __construct(
        private AccountCategoryChangeRequestRepositoryInterface $requestRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(RejectAccountCategoryChangeRequestInputPort $input, RejectAccountCategoryChangeRequestOutputPort $output): void
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

        $request->reject($reviewerAccountIdentifier, $input->rejectionReason());
        $this->requestRepository->save($request);

        $output->setRequest($request);
    }
}
