<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestNotFoundException;
use Source\Account\Account\Application\Service\AccountSnapshotDocumentServiceInterface;
use Source\Account\Account\Domain\Factory\AccountSnapshotFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotProfile;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotStatus;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Throwable;

readonly class RejectAccountSnapshotChangeRequest implements RejectAccountSnapshotChangeRequestInterface
{
    public function __construct(
        private AccountSnapshotChangeRequestRepositoryInterface $accountSnapshotChangeRequestRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AccountRepositoryInterface $accountRepository,
        private AccountSnapshotFactoryInterface $accountSnapshotFactory,
        private AccountSnapshotDocumentServiceInterface $accountSnapshotDocumentService,
        private AccountSnapshotRepositoryInterface $accountSnapshotRepository,
    ) {
    }

    public function process(RejectAccountSnapshotChangeRequestInputPort $input, RejectAccountSnapshotChangeRequestOutputPort $output): void
    {
        $request = $this->accountSnapshotChangeRequestRepository->findById($input->requestIdentifier());
        if ($request === null) {
            throw new AccountSnapshotChangeRequestNotFoundException();
        }

        $reviewerAccountIdentifier = $input->principal()->accountIdentifier();
        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::ACCOUNT_SNAPSHOT_CHANGE_REQUEST_MANAGE,
            Resource::account($reviewerAccountIdentifier),
        )) {
            throw new AccountSnapshotChangeRequestForbiddenException();
        }

        $identifier = $this->accountSnapshotFactory->createIdentifier();
        $request->reject($reviewerAccountIdentifier, $input->rejectionReason(), $identifier);

        $account = $this->accountRepository->findById($request->accountIdentifier()) ?? throw new AccountNotFoundException();
        $documents = $this->accountSnapshotDocumentService->capture($account->accountIdentifier(), $identifier, $account->documents()->all());

        try {
            $snapshot = $this->accountSnapshotFactory->create(
                $identifier,
                $account->accountIdentifier(),
                AccountSnapshotStatus::REJECTED,
                AccountSnapshotProfile::fromAccount($account),
                $documents,
            );
            $this->accountSnapshotRepository->save($snapshot);
            $this->accountSnapshotChangeRequestRepository->save($request);
        } catch (Throwable $exception) {
            $this->accountSnapshotDocumentService->delete($documents);

            throw $exception;
        }

        $output->setRequest($request);
    }
}
