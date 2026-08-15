<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\Service\PrincipalWikiScopeResolverInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Principal\Infrastructure\Service\PrincipalConditionValueResolver;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalConditionValueResolverTest extends TestCase
{
    public function testResolveReturnsLiteralValue(): void
    {
        $resolver = new PrincipalConditionValueResolver($this->scopeResolver());
        $principal = $this->principal();

        $this->assertSame('literal', $resolver->resolve('literal', $principal));
        $this->assertTrue($resolver->resolve(true, $principal));
        $this->assertFalse($resolver->resolve(false, $principal));
    }

    public function testResolvePrincipalValues(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()));
        $agencyWikiIdentifiers = [StrTestHelper::generateUuid()];
        $groupWikiIdentifiers = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        $talentGroupWikiIdentifiers = [StrTestHelper::generateUuid()];
        $talentWikiIdentifiers = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        $affiliatedTalentWikiIdentifiers = [StrTestHelper::generateUuid()];

        /** @var PrincipalWikiScopeResolverInterface&Mockery\MockInterface $scopeResolver */
        $scopeResolver = Mockery::mock(PrincipalWikiScopeResolverInterface::class);
        $scopeResolver->shouldReceive('agencyWikiIdentifiers')->once()->with($principal)->andReturn($agencyWikiIdentifiers);
        $scopeResolver->shouldReceive('groupWikiIdentifiers')->once()->with($principal)->andReturn($groupWikiIdentifiers);
        $scopeResolver->shouldReceive('talentGroupWikiIdentifiers')->once()->with($principal)->andReturn($talentGroupWikiIdentifiers);
        $scopeResolver->shouldReceive('talentWikiIdentifiers')->once()->with($principal)->andReturn($talentWikiIdentifiers);
        $scopeResolver->shouldReceive('affiliatedTalentWikiIdentifiers')->once()->with($principal)->andReturn($affiliatedTalentWikiIdentifiers);

        $resolver = new PrincipalConditionValueResolver($scopeResolver);

        $this->assertSame($agencyWikiIdentifiers, $resolver->resolve(ConditionValue::PRINCIPAL_AGENCY_WIKI_IDENTIFIERS, $principal));
        $this->assertSame($groupWikiIdentifiers, $resolver->resolve(ConditionValue::PRINCIPAL_GROUP_WIKI_IDENTIFIERS, $principal));
        $this->assertSame($talentGroupWikiIdentifiers, $resolver->resolve(ConditionValue::PRINCIPAL_TALENT_GROUP_WIKI_IDENTIFIERS, $principal));
        $this->assertSame($talentWikiIdentifiers, $resolver->resolve(ConditionValue::PRINCIPAL_TALENT_WIKI_IDENTIFIERS, $principal));
        $this->assertSame($affiliatedTalentWikiIdentifiers, $resolver->resolve(ConditionValue::PRINCIPAL_AFFILIATED_TALENT_WIKI_IDENTIFIERS, $principal));
        $this->assertSame((string) $principalIdentifier, $resolver->resolve(ConditionValue::PRINCIPAL_ID, $principal));
    }

    public function testResolveReturnsEmptyArrayWhenPrincipalHasNoScope(): void
    {
        $resolver = new PrincipalConditionValueResolver($this->scopeResolver());

        $this->assertSame([], $resolver->resolve(ConditionValue::PRINCIPAL_AGENCY_WIKI_IDENTIFIERS, $this->principal()));
        $this->assertSame([], $resolver->resolve(ConditionValue::PRINCIPAL_AFFILIATED_TALENT_WIKI_IDENTIFIERS, $this->principal()));
    }

    private function principal(): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
        );
    }

    private function scopeResolver(): PrincipalWikiScopeResolverInterface
    {
        /** @var PrincipalWikiScopeResolverInterface&Mockery\MockInterface $scopeResolver */
        $scopeResolver = Mockery::mock(PrincipalWikiScopeResolverInterface::class);
        $scopeResolver->shouldReceive('agencyWikiIdentifiers')->andReturn([]);
        $scopeResolver->shouldReceive('groupWikiIdentifiers')->andReturn([]);
        $scopeResolver->shouldReceive('talentGroupWikiIdentifiers')->andReturn([]);
        $scopeResolver->shouldReceive('talentWikiIdentifiers')->andReturn([]);
        $scopeResolver->shouldReceive('affiliatedTalentWikiIdentifiers')->andReturn([]);

        return $scopeResolver;
    }
}
