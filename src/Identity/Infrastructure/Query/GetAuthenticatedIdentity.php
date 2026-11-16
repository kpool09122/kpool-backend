<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Models\Account\Account as AccountModel;
use Application\Models\Account\Delegation as DelegationModel;
use Application\Models\Account\Principal as AccountPrincipalModel;
use Application\Models\Identity\Identity as IdentityModel;
use Application\Models\Identity\IdentitySocialConnection as IdentitySocialConnectionModel;
use Application\Models\SiteManagement\Policy as SiteManagementPolicyModel;
use Application\Models\SiteManagement\Principal as SiteManagementPrincipalModel;
use Application\Models\SiteManagement\RolePolicyAttachment;
use Illuminate\Database\Eloquent\Collection;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Delegation\Application\Exception\DelegationUnavailableException;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\UseCase\Query\AuthenticatedAccountReferenceReadModel;
use Source\Identity\Application\UseCase\Query\AuthenticatedAccountSummaryReadModel;
use Source\Identity\Application\UseCase\Query\AuthenticatedIdentityReadModel;
use Source\Identity\Application\UseCase\Query\AuthenticationMethodsReadModel;
use Source\Identity\Application\UseCase\Query\AuthenticationReadModel;
use Source\Identity\Application\UseCase\Query\AuthorizedAccountSelectionReadModel;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInputPort;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Identity\Application\UseCase\Query\SwitchableAccountReadModel;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Infrastructure\Support\ImageUrl;

readonly class GetAuthenticatedIdentity implements GetAuthenticatedIdentityInterface
{
    public function __construct(
        private AccountResolver $accountResolver,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AuthorizationAccountServiceInterface $authorizationAccountService,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     */
    public function process(GetAuthenticatedIdentityInputPort $input): AuthenticationReadModel
    {
        $authorization = request()->attributes->get(ApiAuthenticationContext::class);
        if ($authorization instanceof ApiAuthenticationContext) {
            if ((string) $authorization->identityIdentifier() !== (string) $input->identityIdentifier()
                || ! IdentityModel::query()->whereKey((string) $input->identityIdentifier())->exists()) {
                throw new IdentityNotFoundException();
            }

            return $this->authorizedSelection($authorization);
        }

        $model = IdentityModel::query()
            ->withCount('passkeyCredentials')
            ->addSelect([
                'linked_social_providers' => IdentitySocialConnectionModel::query()
                    ->selectRaw("COALESCE(jsonb_agg(DISTINCT provider ORDER BY provider), '[]'::jsonb)")
                    ->whereColumn('identity_id', 'identities.id'),
            ])
            ->where('id', (string) $input->identityIdentifier())
            ->first();

        if ($model === null) {
            throw new IdentityNotFoundException();
        }

        /** @var AccountContext|null $accountContext */
        $accountContext = null;

        try {
            $accountContext = $this->accountResolver->resolve($input->identityIdentifier());
        } catch (AccountNotFoundException) {
        }

        $account = null;
        $originalAccount = null;
        $switchableAccounts = [];
        if ($accountContext !== null) {
            $accountModel = AccountModel::query()
                ->select([
                    'id',
                    'email',
                    'type',
                    'name',
                    'status',
                    'category',
                    'phone',
                    'address_country_code',
                    'address_administrative_area_code',
                    'address_postal_code',
                    'address_locality',
                    'address_line1',
                    'address_line2',
                ])
                ->where('id', (string) $accountContext->principal()->accountIdentifier())
                ->first();

            if ($accountModel !== null) {
                $account = new AuthenticatedAccountSummaryReadModel(
                    accountIdentifier: $accountModel->id,
                    email: $accountModel->email,
                    type: $accountModel->type,
                    name: $accountModel->name,
                    status: $accountModel->status,
                    accountCategory: $accountModel->category,
                    phone: $accountModel->phone,
                    address: self::address($accountModel),
                );
            }

            $originalAccountModel = AccountModel::query()
                ->select(['id', 'name'])
                ->where('id', (string) $accountContext->originalAccountIdentifier())
                ->first();
            if ($originalAccountModel !== null) {
                $originalAccount = self::accountReference($originalAccountModel);
            }

            $originalPrincipal = $this->principalRepository->findById($accountContext->originalPrincipalIdentifier());
            if ($originalPrincipal !== null) {
                /** @var Collection<int, DelegationModel> $delegations */
                $delegations = DelegationModel::query()
                    ->with('delegatorAccount:id,name')
                    ->where('delegate_account_id', (string) $accountContext->originalAccountIdentifier())
                    ->where('status', 'approved')
                    ->whereHas('delegatorAccount')
                    ->orderBy('id')
                    ->get();

                foreach ($delegations as $delegation) {
                    $delegatorAccount = $delegation->delegatorAccount;
                    if (! $delegatorAccount instanceof AccountModel) {
                        continue;
                    }

                    if (! $this->policyEvaluator->evaluate(
                        $originalPrincipal,
                        Action::DELEGATION_ACCOUNT_SWITCH,
                        Resource::delegationAccount(
                            $accountContext->originalAccountIdentifier(),
                            new DelegationIdentifier($delegation->id),
                            new AccountIdentifier($delegation->delegator_account_id),
                        ),
                    )) {
                        continue;
                    }

                    $switchableAccounts[] = new SwitchableAccountReadModel(
                        delegationIdentifier: $delegation->id,
                        accountIdentifier: $delegation->delegator_account_id,
                        account: self::accountReference($delegatorAccount),
                        isCurrent: (string) $accountContext->delegationIdentifier() === $delegation->id,
                    );
                }
            }
        }

        $siteManagementPrincipal = $accountContext === null ? null : SiteManagementPrincipalModel::query()
            ->where('identity_id', (string) $input->identityIdentifier())
            ->where('account_id', (string) $accountContext->principal()->accountIdentifier())
            ->first();

        return new AuthenticatedIdentityReadModel(
            identityIdentifier: $model->id,
            identityName: $model->identity_name,
            email: $model->email,
            language: $model->language,
            profileImage: ImageUrl::fromPath($model->profile_image),
            accountIdentifier: $accountContext === null ? null : (string) $accountContext->principal()->accountIdentifier(),
            accountPrincipalIdentifier: $accountContext === null ? null : (string) $accountContext->principal()->principalIdentifier(),
            accountType: $accountContext?->nullableAccountType()?->value,
            authenticationMethods: new AuthenticationMethodsReadModel(
                passkeyCount: $model->passkey_credentials_count,
                linkedSocialProviders: $model->linked_social_providers,
            ),
            accountPolicies: $accountContext?->accountPolicies() ?? [],
            account: $account,
            originalAccount: $originalAccount,
            delegationIdentifier: $accountContext?->delegationIdentifier() === null
                ? null
                : (string) $accountContext->delegationIdentifier(),
            switchableAccounts: $switchableAccounts,
            siteManagementPrincipalIdentifier: $siteManagementPrincipal?->id,
            accountPrincipalStatus: $accountContext?->principal()->status()->value,
            siteManagementPrincipalStatus: $siteManagementPrincipal?->status,
            siteManagementPolicies: $siteManagementPrincipal === null ? [] : $this->siteManagementPolicies($siteManagementPrincipal),
            accountIdentityCount: $accountContext === null ? 0 : AccountPrincipalModel::query()
                ->where('account_id', (string) $accountContext->principal()->accountIdentifier())
                ->whereHas('identity')
                ->distinct()
                ->count('identity_id'),
            siteManagementPrincipalCount: $accountContext === null ? 0 : SiteManagementPrincipalModel::query()
                ->where('account_id', (string) $accountContext->principal()->accountIdentifier())
                ->whereHas('identity')
                ->count(),
        );
    }

    private function authorizedSelection(ApiAuthenticationContext $authorization): AuthorizedAccountSelectionReadModel
    {
        $current = null;

        try {
            $current = $this->accountResolver->resolve($authorization->identityIdentifier());
        } catch (AccountNotFoundException|DelegationUnavailableException) {
        }

        $switchable = [];
        $originalAccount = null;

        try {
            $original = $this->authorizationAccountService->resolveOriginalAccount($authorization->identityIdentifier());
            if ((string) $original->accountIdentifier !== (string) $authorization->originalAccountIdentifier()
                || (string) $original->principalIdentifier !== (string) $authorization->originalPrincipalIdentifier()) {
                throw new AuthorizationAccountUnavailableException();
            }

            $originalAccount = new AuthenticatedAccountReferenceReadModel((string) $original->accountIdentifier, $original->name);
            $switchable[] = new SwitchableAccountReadModel(
                null,
                (string) $original->accountIdentifier,
                $originalAccount,
                $current !== null && $current->delegationIdentifier() === null,
            );

            $delegations = DelegationModel::query()
                ->where('delegate_account_id', (string) $original->accountIdentifier)
                ->where('status', 'approved')
                ->withWhereHas('delegatorAccount', static fn ($query) => $query->where('status', AccountStatus::ACTIVE->value))
                ->get();
            foreach ($delegations as $delegation) {
                $account = $delegation->delegatorAccount;
                if ($account === null || ! $this->authorizationAccountService->allowsDelegatedTarget(
                    $original,
                    new AccountIdentifier($account->id),
                    new DelegationIdentifier($delegation->id),
                )) {
                    continue;
                }

                $switchable[] = new SwitchableAccountReadModel(
                    $delegation->id,
                    $account->id,
                    self::accountReference($account),
                    (string) $current?->delegationIdentifier() === $delegation->id,
                );
            }
        } catch (AuthorizationAccountUnavailableException) {
        }
        usort($switchable, static fn (SwitchableAccountReadModel $a, SwitchableAccountReadModel $b): int => strcmp($a->toArray()['accountIdentifier'], $b->toArray()['accountIdentifier']));
        $currentModel = $current === null ? null : AccountModel::query()->select(['id', 'name'])->whereKey((string) $current->principal()->accountIdentifier())->first();

        return new AuthorizedAccountSelectionReadModel(
            (string) $authorization->identityIdentifier(),
            $currentModel === null ? null : self::accountReference($currentModel),
            $current === null ? null : (string) $current->principal()->principalIdentifier(),
            $current?->delegationIdentifier() === null ? null : (string) $current->delegationIdentifier(),
            $originalAccount,
            $switchable,
        );
    }

    /** @return array<int, array{policyIdentifier: string, name: string, statements: array<int, array{effect: string, actions: array<int, string>, resourceTypes: array<int, string>, condition: string|null}>}> */
    private function siteManagementPolicies(SiteManagementPrincipalModel $principal): array
    {
        $policyIdentifiers = RolePolicyAttachment::query()
            ->select('policy_id')
            ->join('site_management_principal_group_role_attachments as roles', 'roles.role_id', '=', 'site_management_role_policy_attachments.role_id')
            ->join('site_management_principal_group_memberships as memberships', 'memberships.principal_group_id', '=', 'roles.principal_group_id')
            ->join('site_management_principal_groups as groups', 'groups.id', '=', 'memberships.principal_group_id')
            ->join('site_management_roles as role_models', 'role_models.id', '=', 'roles.role_id')
            ->where('memberships.principal_id', $principal->id)
            ->where('groups.account_id', $principal->account_id)
            ->where(static fn ($query) => $query->whereNull('role_models.account_id')->orWhere('role_models.account_id', $principal->account_id));

        return SiteManagementPolicyModel::query()
            ->whereIn('id', $policyIdentifiers)
            ->where(static fn ($query) => $query->whereNull('account_id')->orWhere('account_id', $principal->account_id))
            ->orderBy('id')
            ->get()
            ->map(static fn (SiteManagementPolicyModel $policy): array => [
                'policyIdentifier' => $policy->id,
                'name' => $policy->name,
                'statements' => $policy->statementValues(),
            ])->all();
    }

    private static function accountReference(AccountModel $account): AuthenticatedAccountReferenceReadModel
    {
        return new AuthenticatedAccountReferenceReadModel(
            accountIdentifier: $account->id,
            name: $account->name,
        );
    }

    /** @return array<string, mixed>|null */
    private static function address(AccountModel $model): ?array
    {
        if (
            $model->address_country_code === null
            && $model->address_administrative_area_code === null
            && $model->address_postal_code === null
            && $model->address_locality === null
            && $model->address_line1 === null
            && $model->address_line2 === null
        ) {
            return null;
        }

        return [
            'countryCode' => $model->address_country_code,
            'administrativeAreaCode' => $model->address_administrative_area_code,
            'postalCode' => $model->address_postal_code,
            'locality' => $model->address_locality,
            'addressLine1' => $model->address_line1,
            'addressLine2' => $model->address_line2,
        ];
    }
}
