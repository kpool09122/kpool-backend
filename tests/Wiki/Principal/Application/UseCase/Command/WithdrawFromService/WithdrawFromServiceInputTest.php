<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\WithdrawFromService;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;

class WithdrawFromServiceInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new WithdrawFromServiceInput($identityIdentifier);

        $this->assertSame($identityIdentifier, $subject->identityIdentifier());
    }
}
