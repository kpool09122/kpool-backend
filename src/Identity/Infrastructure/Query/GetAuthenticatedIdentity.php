<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Models\Account\Account as AccountModel;
use Application\Models\Account\Delegation as DelegationModel;
use Application\Models\Identity\Identity as IdentityModel;
use Illuminate\Database\Eloquent\Collection;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Identity\Application\UseCase\Query\AuthenticatedAccountReferenceReadModel;
use Source\Identity\Application\UseCase\Query\AuthenticatedAccountSummaryReadModel;
use Source\Identity\Application\UseCase\Query\AuthenticatedIdentityReadModel;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInputPort;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Identity\Application\UseCase\Query\SwitchableAccountReadModel;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Infrastructure\Support\ImageUrl;

readonly class GetAuthenticatedIdentity implements GetAuthenticatedIdentityInterface
{
    public function __construct(
        private AccountResolver $accountResolver,
        private AccountRepositoryInterface $accountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     */
    public function process(GetAuthenticatedIdentityInputPort $input): AuthenticatedIdentityReadModel
    {
        $model = IdentityModel::query()
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

            if ($this->canSwitchAccounts($accountContext)) {
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

                    $switchableAccounts[] = new SwitchableAccountReadModel(
                        delegationIdentifier: $delegation->id,
                        accountIdentifier: $delegation->delegator_account_id,
                        account: self::accountReference($delegatorAccount),
                        isCurrent: (string) $accountContext->delegationIdentifier() === $delegation->id,
                    );
                }
            }
        }

        return new AuthenticatedIdentityReadModel(
            identityIdentifier: $model->id,
            identityName: $model->identity_name,
            email: $model->email,
            language: $model->language,
            profileImage: ImageUrl::fromPath($model->profile_image),
            accountIdentifier: $accountContext === null ? null : (string) $accountContext->principal()->accountIdentifier(),
            accountPrincipalIdentifier: $accountContext === null ? null : (string) $accountContext->principal()->principalIdentifier(),
            accountType: $accountContext?->accountType()->value,
            accountPolicies: $accountContext?->accountPolicies() ?? [],
            account: $account,
            originalAccount: $originalAccount,
            delegationIdentifier: $accountContext?->delegationIdentifier() === null
                ? null
                : (string) $accountContext->delegationIdentifier(),
            switchableAccounts: $switchableAccounts,
        );
    }

    private static function accountReference(AccountModel $account): AuthenticatedAccountReferenceReadModel
    {
        return new AuthenticatedAccountReferenceReadModel(
            accountIdentifier: $account->id,
            name: $account->name,
        );
    }

    private function canSwitchAccounts(AccountContext $accountContext): bool
    {
        $originalPrincipal = $this->principalRepository->findById($accountContext->originalPrincipalIdentifier());
        $originalAccount = $this->accountRepository->findById($accountContext->originalAccountIdentifier());
        if ($originalPrincipal === null || $originalAccount === null) {
            return false;
        }

        return $this->policyEvaluator->evaluate(
            $originalPrincipal,
            Action::DELEGATION_ACCOUNT_SWITCH,
            Resource::account(
                $originalAccount->accountIdentifier(),
                $originalAccount->type(),
                $originalAccount->accountCategory(),
            ),
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
