<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DeletePolicy;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy\DeletePolicyInput;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

class DeletePolicyInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $policyIdentifier = new PolicyIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new DeletePolicyInput($policyIdentifier);

        $this->assertSame($policyIdentifier, $subject->policyIdentifier());
    }
}
