<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\AccountSnapshotChangeRequest;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInputPort;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestOutputPort;
use Source\Account\Account\Infrastructure\Factory\AccountSnapshotChangeRequestReadModelFactory;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class GetAccountSnapshotChangeRequest implements GetAccountSnapshotChangeRequestInterface
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator, private AccountSnapshotChangeRequestReadModelFactory $accountSnapshotChangeRequestReadModelFactory)
    {
    }

    public function process(GetAccountSnapshotChangeRequestInputPort $input, GetAccountSnapshotChangeRequestOutputPort $output): void
    {
        $request = AccountSnapshotChangeRequest::query()->with(['snapshot.documents', 'account.documents', 'reviewedSnapshot.documents'])->withCurrentSnapshotIdentifier()->find((string) $input->requestIdentifier()) ?? throw new AccountSnapshotChangeRequestNotFoundException();
        $owner = $request->account_id === (string) $input->principal()->accountIdentifier();
        if (! $this->policyEvaluator->evaluate($input->principal(), $owner ? Action::READ : Action::ACCOUNT_SNAPSHOT_CHANGE_REQUEST_MANAGE, Resource::account($input->principal()->accountIdentifier()))) {
            throw new AccountSnapshotChangeRequestForbiddenException();
        }
        $output->output($this->accountSnapshotChangeRequestReadModelFactory->create($request));
    }
}
