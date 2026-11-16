<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class AuthorizedAccountSelectionReadModel extends AuthenticationReadModel
{
    /** @param list<SwitchableAccountReadModel> $switchableAccounts */
    public function __construct(
        private string $identityIdentifier,
        private ?AuthenticatedAccountReferenceReadModel $account,
        private ?string $accountPrincipalIdentifier,
        private ?string $delegationIdentifier,
        private ?AuthenticatedAccountReferenceReadModel $originalAccount,
        private array $switchableAccounts,
    ) {
    }

    /** @return array{identityIdentifier: string, accountIdentifier: string|null, accountPrincipalIdentifier: string|null, account: array{accountIdentifier: string, name: string}|null, delegationIdentifier: string|null, originalAccount: array{accountIdentifier: string, name: string}|null, switchableAccounts: list<array{delegationIdentifier: string|null, accountIdentifier: string, account: array{accountIdentifier: string, name: string}, isCurrent: bool}>} */
    public function toArray(): array
    {
        return [
            'identityIdentifier' => $this->identityIdentifier,
            'accountIdentifier' => $this->account?->toArray()['accountIdentifier'],
            'accountPrincipalIdentifier' => $this->accountPrincipalIdentifier,
            'account' => $this->account?->toArray(),
            'delegationIdentifier' => $this->delegationIdentifier,
            'originalAccount' => $this->originalAccount?->toArray(),
            'switchableAccounts' => array_map(static fn (SwitchableAccountReadModel $account): array => $account->toArray(), $this->switchableAccounts),
        ];
    }
}
