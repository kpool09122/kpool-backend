<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class RoleTest extends TestCase
{
    public function testEntityPreservesIdentityAndData(): void
    {
        $id = new RoleIdentifier('69200000-0000-7000-8000-000000000099');
        $entity = new Role($id, 'test', [], null);
        self::assertSame($id, $entity->roleIdentifier());
        self::assertSame('test', $entity->name());
        self::assertSame([], $entity->policies());
    }

    public function testPoliciesCanBeAddedOnceAndRemovedByIdentifier(): void
    {
        $role = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'Manager', [], null);
        $firstId = new PolicyIdentifier(StrTestHelper::generateUuid());
        $secondId = new PolicyIdentifier(StrTestHelper::generateUuid());
        self::assertFalse($role->hasPolicy($firstId));
        $role->addPolicy(new Policy($firstId, 'first', [], null));
        $role->addPolicy(new Policy(new PolicyIdentifier((string) $firstId), 'first', [], null));
        $role->addPolicy(new Policy($secondId, 'second', [], null));
        self::assertSame([$firstId, $secondId], $role->policies());
        self::assertTrue($role->hasPolicy(new PolicyIdentifier((string) $firstId)));

        $role->removePolicy(new PolicyIdentifier((string) $firstId));
        self::assertFalse($role->hasPolicy($firstId));
        self::assertSame([$secondId], $role->policies());
        $role->removePolicy($firstId);
        self::assertSame([$secondId], $role->policies());
        $role->removePolicy($secondId);
        self::assertSame([], $role->policies());
    }

    #[DataProvider('policyScopes')]
    public function testPolicyAccountScope(?AccountIdentifier $roleAccount, ?AccountIdentifier $policyAccount, bool $allowed): void
    {
        $role = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'Manager', [], $roleAccount);
        $policy = new Policy(new PolicyIdentifier(StrTestHelper::generateUuid()), 'policy', [], $policyAccount);
        if (! $allowed) {
            $this->expectException(InvalidArgumentException::class);
        }
        $role->addPolicy($policy);
        self::assertTrue($role->hasPolicy($policy->policyIdentifier()));
    }

    /** @return iterable<string, array{?AccountIdentifier, ?AccountIdentifier, bool}> */
    public static function policyScopes(): iterable
    {
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $otherAccount = new AccountIdentifier('00000000-0000-7000-8000-000000000010');
        yield 'system role with system policy' => [null, null, true];
        yield 'system role with account policy' => [null, $account, false];
        yield 'account role with system policy' => [$account, null, true];
        yield 'account role with own policy' => [$account, new AccountIdentifier((string) $account), true];
        yield 'account role with foreign policy' => [$account, $otherAccount, false];
    }
}
