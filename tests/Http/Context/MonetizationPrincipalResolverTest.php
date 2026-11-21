<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\AccountContext;
use Application\Http\Context\MonetizationPrincipalResolver;
use Mockery;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MonetizationPrincipalResolverTest extends TestCase
{
    public function testResolveUsesEffectiveAccountContext(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $monetizationPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $context = new AccountContext(
            new AccountPrincipal(new AccountPrincipalIdentifier(StrTestHelper::generateUuid()), $identityId, $accountId),
            AccountType::CORPORATION,
            AccountStatus::ACTIVE,
            AccountCategory::AGENCY,
        );
        $principal = new Principal($monetizationPrincipalId, $identityId, $accountId, PrincipalStatus::SUSPENDED);
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->once()->with($identityId, $accountId)->andReturn($principal);

        $this->assertSame($monetizationPrincipalId, (new MonetizationPrincipalResolver($repository))->resolve($context));
    }

    public function testResolveThrowsWhenIdentityHasNoPrincipalForEffectiveAccount(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $context = new AccountContext(
            new AccountPrincipal(new AccountPrincipalIdentifier(StrTestHelper::generateUuid()), $identityId, $accountId),
            AccountType::CORPORATION,
            AccountStatus::ACTIVE,
            AccountCategory::AGENCY,
        );
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->andReturn(null);

        $this->expectException(PrincipalNotFoundException::class);
        (new MonetizationPrincipalResolver($repository))->resolve($context);
    }
}
