<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Infrastructure\Repository\PolicyRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PolicyRepositoryTest extends TestCase
{
    public function testAccountPolicyAndSystemPolicyCanShareNameWithoutLosingScope(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        $id = new PolicyIdentifier(StrTestHelper::generateUuid());
        $systemId = new PolicyIdentifier(StrTestHelper::generateUuid());
        $repository = new PolicyRepository();
        $repository->save(new Policy($id, 'read', [], $account));
        $repository->save(new Policy($systemId, 'read', [], null));
        self::assertSame([], $repository->findByIds([]));
        self::assertSame([], $repository->findByIds([new PolicyIdentifier(StrTestHelper::generateUuid())]));
        self::assertCount(2, $repository->findByIds([$id, $systemId]));
        self::assertSame((string) $account, (string) $repository->findByIds([$id])[0]->accountIdentifier());
        self::assertFalse($repository->findByIds([$id])[0]->isSystemPolicy());
        self::assertTrue($repository->findByIds([$systemId])[0]->isSystemPolicy());
    }
}
