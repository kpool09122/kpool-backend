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
            Action::UPDATE,
            Resource::account($accountIdentifier),
        )) {
            throw new AccountUpdateForbiddenException();
        }

        $model = AccountModel::query()
            ->select(['id', 'email', 'type', 'name', 'status', 'category'])
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
        );
    }
}
