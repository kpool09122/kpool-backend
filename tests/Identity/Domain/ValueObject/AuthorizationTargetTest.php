<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

class AuthorizationTargetTest extends TestCase
{
    public function testItPreservesAccountAndOptionalDelegationIdentifiers(): void
    {
        $account = new AccountIdentifier('0199f700-0000-7000-8000-000000000001');
        $delegation = new DelegationIdentifier('0199f700-0000-7000-8000-000000000002');
        $target = new AuthorizationTarget($account, $delegation);
        $this->assertSame($account, $target->accountIdentifier);
        $this->assertSame($delegation, $target->delegationIdentifier);
        $this->assertNull((new AuthorizationTarget($account))->delegationIdentifier);
    }
}
