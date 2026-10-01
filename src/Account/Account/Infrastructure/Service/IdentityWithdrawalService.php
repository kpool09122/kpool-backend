<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use Application\Models\Account\Account as AccountEloquent;
use Application\Models\Account\AccountDocument as AccountDocumentEloquent;
use Application\Models\Account\Principal as PrincipalEloquent;
use DateTimeImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Application\Service\IdentityWithdrawalServiceInterface;
use Source\Account\Account\Domain\Event\AccountDeleting;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Account\Account\Domain\Service\IdentityWithdrawalEligibility;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Throwable;

readonly class IdentityWithdrawalService implements IdentityWithdrawalServiceInterface
{
    public function __construct(
        private IdentityWithdrawalEligibility $identityWithdrawalEligibility,
        private DocumentStorageServiceInterface $documentStorageService,
        private CurrentAccountServiceInterface $currentAccountService,
        private LoggerInterface $logger,
        private Dispatcher $events,
    ) {
    }

    /** @throws IdentityWithdrawalNotAllowedException */
    public function withdraw(IdentityIdentifier $identityIdentifier, DateTimeImmutable $archivedAt): void
    {
        $principals = PrincipalEloquent::query()->where('identity_id', (string) $identityIdentifier)
            ->orderBy('id')->lockForUpdate()->get();
        if ($principals->isEmpty()) {
            throw new IdentityWithdrawalNotAllowedException('An account membership is required for self-service withdrawal.');
        }
        $accounts = AccountEloquent::query()->whereIn('id', $principals->pluck('account_id'))
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        // Validate every actual membership before creating any Account archive.
        foreach ($principals as $principal) {
            $account = $accounts->get($principal->account_id);
            if ($account === null) {
                throw new IdentityWithdrawalNotAllowedException('The account membership no longer exists.');
            }
            $membershipGroupIds = DB::table('account_principal_group_memberships')
                ->where('principal_id', $principal->id)->lockForUpdate()->pluck('principal_group_id');
            $groupIds = DB::table('account_principal_groups')->whereIn('id', $membershipGroupIds)
                ->lockForUpdate()->pluck('id');
            $roleIds = DB::table('account_principal_group_role_attachments')->whereIn('principal_group_id', $groupIds)
                ->lockForUpdate()->pluck('role_id');
            $ownerRoles = DB::table('account_roles')->whereIn('id', $roleIds)->whereNull('account_id')
                ->where('name', Role::OWNER)->lockForUpdate()->get();
            $this->identityWithdrawalEligibility->assertAllowed(
                AccountCategory::from($account->category),
                $account->type === null ? null : AccountType::from($account->type),
                $ownerRoles->isNotEmpty(),
            );
        }

        foreach ($principals as $principal) {
            DB::table('archived_principals')->insert([
                'identity_id' => (string) $identityIdentifier,
                'principal_type' => 'account',
                'principal_id' => $principal->id,
                'account_id' => $principal->account_id,
                'archived_at' => $archivedAt,
            ]);
        }

        foreach ($accounts as $account) {
            if ($account->type !== AccountType::INDIVIDUAL->value) {
                continue;
            }
            DB::table('archived_accounts')->insert([
                'account_id' => $account->id,
                'account_category' => $account->category,
                'account_type' => $account->type,
                'archived_at' => $archivedAt,
            ]);
            foreach (AccountDocumentEloquent::query()->where('account_id', $account->id)->get() as $document) {
                $this->documentStorageService->deleteAfterCommit(new DocumentPath($document->document_path));
            }
            $this->events->dispatch(new AccountDeleting(new AccountIdentifier($account->id)));
            // These account-scoped authorization tables intentionally have no Account FK.
            DB::table('account_roles')->where('account_id', $account->id)->delete();
            DB::table('account_policies')->where('account_id', $account->id)->delete();
            DB::table('accounts')->where('id', $account->id)->delete();
        }
        DB::table('account_principals')->where('identity_id', (string) $identityIdentifier)->delete();
        DB::afterCommit(function () use ($identityIdentifier): void {
            try {
                $this->currentAccountService->forget($identityIdentifier);
            } catch (Throwable $exception) {
                $this->logger->warning('Failed to clear withdrawn identity account context.', ['exception' => $exception]);
            }
        });
    }
}
