<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class SwitchableAccountReadModel
{
    public function __construct(
        private string $delegationIdentifier,
        private string $accountIdentifier,
        private AuthenticatedAccountReferenceReadModel $account,
        private bool $isCurrent,
    ) {
    }

    /**
     * @return array{
     *     delegationIdentifier: string,
     *     accountIdentifier: string,
     *     account: array{accountIdentifier: string, name: string},
     *     isCurrent: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'delegationIdentifier' => $this->delegationIdentifier,
            'accountIdentifier' => $this->accountIdentifier,
            'account' => $this->account->toArray(),
            'isCurrent' => $this->isCurrent,
        ];
    }
}
