<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\AccountSnapshotChangeRequest;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests\ListAccountSnapshotChangeRequestsInputPort;
use Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests\ListAccountSnapshotChangeRequestsInterface;
use Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests\ListAccountSnapshotChangeRequestsOutputPort;
use Source\Account\Account\Infrastructure\Factory\AccountSnapshotChangeRequestReadModelFactory;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class ListAccountSnapshotChangeRequests implements ListAccountSnapshotChangeRequestsInterface
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator, private AccountSnapshotChangeRequestReadModelFactory $accountSnapshotChangeRequestReadModelFactory)
    {
    }

    public function process(ListAccountSnapshotChangeRequestsInputPort $input, ListAccountSnapshotChangeRequestsOutputPort $output): void
    {
        if (! $this->policyEvaluator->evaluate($input->principal(), Action::ACCOUNT_SNAPSHOT_CHANGE_REQUEST_MANAGE, Resource::account($input->principal()->accountIdentifier()))) {
            throw new AccountSnapshotChangeRequestForbiddenException();
        }
        $query = AccountSnapshotChangeRequest::query()->with(['snapshot.documents', 'account.documents', 'reviewedSnapshot.documents'])->withCurrentSnapshotIdentifier();
        if ($input->status() !== null) {
            $query->where('status', $input->status());
        }
        $paginator = $query->orderByDesc('requested_at')->orderByDesc('id')->paginate($input->perPage(), ['*'], 'page', $input->page());
        $requests = array_map($this->accountSnapshotChangeRequestReadModelFactory->create(...), $paginator->items());
        $output->output($requests, $paginator->currentPage(), $paginator->lastPage(), $paginator->total(), $paginator->perPage());
    }
}
