<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;

class PolicyTest extends TestCase
{
    public function testEntityPreservesIdentityAndData(): void
    {
        $id = new PolicyIdentifier('69200000-0000-7000-8000-000000000099');
        $entity = new Policy($id, 'test', [], null);
        self::assertSame($id, $entity->policyIdentifier());
        self::assertSame('test', $entity->name());
        self::assertSame([], $entity->statements());
    }
}
