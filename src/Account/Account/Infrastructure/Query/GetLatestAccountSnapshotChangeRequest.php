<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\AccountCategoryChangeRequest;
use Application\Models\Account\AccountSnapshotChangeRequest;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Service\AccountSnapshotDocumentServiceInterface;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestInputPort;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestOutputPort;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotProfile;
use Source\Account\Account\Infrastructure\Factory\AccountSnapshotChangeRequestReadModelFactory;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class GetLatestAccountSnapshotChangeRequest implements GetLatestAccountSnapshotChangeRequestInterface
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator, private AccountSnapshotChangeRequestReadModelFactory $accountSnapshotChangeRequestReadModelFactory, private AccountRepositoryInterface $accountRepository, private AccountSnapshotRepositoryInterface $accountSnapshotRepository, private AccountSnapshotDocumentServiceInterface $accountSnapshotDocumentService)
    {
    }

    public function process(GetLatestAccountSnapshotChangeRequestInputPort $input, GetLatestAccountSnapshotChangeRequestOutputPort $output): void
    {
        if ((string) $input->principal()->accountIdentifier() !== (string) $input->accountIdentifier() || ! $this->policyEvaluator->evaluate($input->principal(), Action::READ, Resource::account($input->accountIdentifier()))) {
            throw new AccountSnapshotChangeRequestForbiddenException();
        }
        $request = AccountSnapshotChangeRequest::query()->with(['snapshot.documents', 'account.documents', 'reviewedSnapshot.documents'])->withCurrentSnapshotIdentifier()->where('account_id', (string) $input->accountIdentifier())->orderByDesc('requested_at')->orderByDesc('id')->first();
        $categoryChangeStatuses = AccountCategoryChangeRequest::query()
            ->where('account_id', (string) $input->accountIdentifier())
            ->whereIn('status', ['approved', 'pending'])
            ->distinct()
            ->pluck('status');
        $requiresApproval = $categoryChangeStatuses->containsStrict('approved');
        $categoryChangePending = $categoryChangeStatuses->containsStrict('pending');
        $account = $this->accountRepository->findById($input->accountIdentifier());
        $current = $this->accountSnapshotRepository->findCurrent($input->accountIdentifier());
        $hasChanges = $account !== null && $current !== null && (! $account->hasRequiredContactForCategoryChange() || ! $current->hasSameContents(
            AccountSnapshotProfile::fromAccount($account),
            $this->accountSnapshotDocumentService->hashes($account->documents()->all()),
        ));
        $output->output($request === null ? null : $this->accountSnapshotChangeRequestReadModelFactory->create($request), $requiresApproval, $hasChanges, $categoryChangePending);
    }
}
