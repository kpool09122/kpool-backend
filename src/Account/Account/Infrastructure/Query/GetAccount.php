<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\Account as AccountModel;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Application\UseCase\Query\AccountReadModel;
use Source\Account\Account\Application\UseCase\Query\GetAccount\GetAccountInputPort;
use Source\Account\Account\Application\UseCase\Query\GetAccount\GetAccountInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class GetAccount implements GetAccountInterface
{
    public function __construct(
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    public function process(GetAccountInputPort $input): AccountReadModel
    {
        $accountIdentifier = $input->accountIdentifier();

        if ((string) $input->principal()->accountIdentifier() !== (string) $accountIdentifier) {
            throw new AccountUpdateForbiddenException();
        }

        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::READ,
            Resource::account($accountIdentifier, $input->accountType()),
        )) {
            throw new AccountUpdateForbiddenException();
        }

        $model = AccountModel::query()
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
            ->where('id', (string) $accountIdentifier)
            ->first();

        if ($model === null) {
            throw new AccountNotFoundException();
        }

        return new AccountReadModel(
            accountIdentifier: $model->id,
            email: $model->email,
            type: $model->type,
            name: $model->name,
            status: $model->status,
            accountCategory: $model->category,
            phone: $model->phone,
            address: self::address($model),
        );
    }

    /**
     * @return array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string|null, addressLine2: string|null}|null
     */
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
