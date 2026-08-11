<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\AuthContextCache;
use Application\Models\Account\Account as AccountModel;
use Application\Models\Identity\Identity as IdentityModel;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Identity\Application\UseCase\Query\AuthenticatedAccountSummaryReadModel;
use Source\Identity\Application\UseCase\Query\AuthenticatedIdentityReadModel;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInputPort;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Infrastructure\Support\ImageUrl;

readonly class GetAuthenticatedIdentity implements GetAuthenticatedIdentityInterface
{
    public function __construct(
        private AccountResolver $accountResolver,
        private AuthContextCache $cache,
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
            $accountContext = $this->cache->resolveAccount(
                $input->identityIdentifier(),
                fn () => $this->accountResolver->resolve($input->identityIdentifier()),
            );
        } catch (AccountNotFoundException) {
            $accountContext = null;
        }

        $account = null;
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
