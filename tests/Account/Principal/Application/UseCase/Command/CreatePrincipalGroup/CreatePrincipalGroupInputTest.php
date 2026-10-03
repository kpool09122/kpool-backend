<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreatePrincipalGroupInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $name = 'name-value';

        $subject = new CreatePrincipalGroupInput($accountIdentifier, $name);

        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
        $this->assertSame($name, $subject->name());
    }
}
