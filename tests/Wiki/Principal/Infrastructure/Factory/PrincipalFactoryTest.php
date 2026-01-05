<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalFactoryTest extends TestCase
{
    /**
     * 正常系: 正しくプリンシパルを生成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $factory = $this->app->make(PrincipalFactoryInterface::class);
        $principal = $factory->create(
            $identityIdentifier,
        );

        $this->assertTrue(UuidValidator::isValid((string)$principal->principalIdentifier()));
        $this->assertSame($identityIdentifier, $principal->identityIdentifier());
        $this->assertSame(Role::NONE, $principal->role());
        $this->assertNull($principal->agencyId());
        $this->assertEmpty($principal->groupIds());
        $this->assertEmpty($principal->talentIds());
        $this->assertNull($principal->delegationIdentifier());
        $this->assertFalse($principal->isDelegatedPrincipal());
        $this->assertTrue($principal->isEnabled());
    }

    /**
     * 正常系: 代理用プリンシパルを正しく生成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateDelegatedPrincipal(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $groupIds = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        $talentIds = [StrTestHelper::generateUuid()];

        $originalPrincipal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::TALENT_ACTOR,
            $agencyId,
            $groupIds,
            $talentIds,
        );

        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $factory = $this->app->make(PrincipalFactoryInterface::class);
        $delegatedPrincipal = $factory->createDelegatedPrincipal(
            $originalPrincipal,
            $delegationIdentifier,
            $delegatedIdentityIdentifier,
        );

        $this->assertTrue(UuidValidator::isValid((string)$delegatedPrincipal->principalIdentifier()));
        $this->assertNotSame(
            (string)$originalPrincipal->principalIdentifier(),
            (string)$delegatedPrincipal->principalIdentifier()
        );
        $this->assertSame($delegatedIdentityIdentifier, $delegatedPrincipal->identityIdentifier());
        $this->assertSame(Role::TALENT_ACTOR, $delegatedPrincipal->role());
        $this->assertSame($agencyId, $delegatedPrincipal->agencyId());
        $this->assertSame($groupIds, $delegatedPrincipal->groupIds());
        $this->assertSame($talentIds, $delegatedPrincipal->talentIds());
        $this->assertSame($delegationIdentifier, $delegatedPrincipal->delegationIdentifier());
        $this->assertTrue($delegatedPrincipal->isDelegatedPrincipal());
        $this->assertTrue($delegatedPrincipal->isEnabled());
    }
}
