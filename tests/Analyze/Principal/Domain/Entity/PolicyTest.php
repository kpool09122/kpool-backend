<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Domain\Entity\Policy;
use Source\Analyze\Principal\Domain\ValueObject\PolicyIdentifier;

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
