<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\AccountContext;
use Application\Http\Context\PrincipalResolver;
use Mockery;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalResolverTest extends TestCase
{
    public function testResolveUsesEffectiveAccountContext(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $wikiPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $context = new AccountContext(
            new AccountPrincipal(new AccountPrincipalIdentifier(StrTestHelper::generateUuid()), $identityId, $accountId),
            AccountType::CORPORATION,
            AccountCategory::AGENCY,
        );
        $principal = Mockery::mock(Principal::class);
        $principal->shouldReceive('principalIdentifier')->once()->andReturn($wikiPrincipalId);
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->once()->with($identityId, $accountId)->andReturn($principal);

        $this->assertSame($wikiPrincipalId, (new PrincipalResolver($repository))->resolve($context));
    }

    public function testResolveThrowsWhenIdentityHasNoPrincipalForEffectiveAccount(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $context = new AccountContext(
            new AccountPrincipal(new AccountPrincipalIdentifier(StrTestHelper::generateUuid()), $identityId, $accountId),
            AccountType::CORPORATION,
            AccountCategory::AGENCY,
        );
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->andReturn(null);

        $this->expectException(PrincipalNotFoundException::class);
        (new PrincipalResolver($repository))->resolve($context);
    }
}
