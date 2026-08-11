<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

readonly class AccountCategoryChangeRequestListItemReadModel
{
    public function __construct(
        private AccountCategoryChangeRequestReadModel $request,
        private AccountReadModel $account,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            ...$this->request->toArray(),
            'account' => $this->account->toArray(),
        ];
    }
}
