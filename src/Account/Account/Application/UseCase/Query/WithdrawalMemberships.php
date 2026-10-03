<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Principal\Domain\Entity\Principal;

readonly class WithdrawalMemberships
{
    /** @param array<Principal> $principals
     * @param array<Account> $accounts
     */
    public function __construct(public array $principals, public array $accounts)
    {
    }
}
