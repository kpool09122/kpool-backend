<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\Service\PrincipalWikiScopeResolverInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Principal\Infrastructure\Service\AffiliationConditionValueResolver;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationConditionValueResolverTest extends TestCase
{
    public function testResolveAffiliatedTalentWikiIdentifiersDelegatesToScopeResolver(): void
    {
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
        );
        $talentWikiIdentifiers = [StrTestHelper::generateUuid()];

        /** @var PrincipalWikiScopeResolverInterface&Mockery\MockInterface $scopeResolver */
        $scopeResolver = Mockery::mock(PrincipalWikiScopeResolverInterface::class);
        $scopeResolver->shouldReceive('affiliatedTalentWikiIdentifiers')
            ->once()
            ->with($principal)
            ->andReturn($talentWikiIdentifiers);

        $resolver = new AffiliationConditionValueResolver($scopeResolver);

        $this->assertSame(
            $talentWikiIdentifiers,
            $resolver->resolve(ConditionValue::PRINCIPAL_AFFILIATED_TALENT_WIKI_IDENTIFIERS, $principal),
        );
    }
}
