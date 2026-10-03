<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyInput;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class CreatePolicyInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $name = 'name-value';
        $statements = [new Statement(Effect::ALLOW, [Action::cases()[0]], [ResourceType::TALENT])];
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new CreatePolicyInput($name, $statements, $accountIdentifier);

        $this->assertSame($name, $subject->name());
        $this->assertSame($statements, $subject->statements());
        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
    }

    public function testAllowsAbsentOptionalValues(): void
    {
        $subject = new CreatePolicyInput('name-value', [], null);
        $this->assertNull($subject->accountIdentifier());
    }
}
