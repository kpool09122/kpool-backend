<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreateRole;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleInput;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

class CreateRoleInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $name = 'name-value';
        $policies = [new PolicyIdentifier('019c9b4c-0000-7000-8000-000000000003')];
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new CreateRoleInput($name, $policies, $accountIdentifier);

        $this->assertSame($name, $subject->name());
        $this->assertSame($policies, $subject->policies());
        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
    }

    public function testAllowsAbsentOptionalValues(): void
    {
        $subject = new CreateRoleInput('name-value', [], null);
        $this->assertNull($subject->accountIdentifier());
    }
}
