<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Application\UseCase\Query\ListMembers;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInput;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class ListMembersInputTest extends TestCase
{
    public function testPreservesExactValueObjects(): void
    {
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000001');
        $principal = new PrincipalIdentifier('00000000-0000-7000-8000-000000000002');
        $input = new ListMembersInput($account, $principal);
        self::assertSame($account, $input->accountIdentifier());
        self::assertSame($principal, $input->principalIdentifier());
    }
}
