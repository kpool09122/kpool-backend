<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
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
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());

        $factory = $this->app->make(PrincipalFactoryInterface::class);
        $principal = $factory->create(
            $identityIdentifier,
        );

        $this->assertTrue(UlidValidator::isValid((string)$principal->principalIdentifier()));
        $this->assertSame($identityIdentifier, $principal->identityIdentifier());
        $this->assertSame(Role::NONE, $principal->role());
        $this->assertNull($principal->agencyId());
        $this->assertEmpty($principal->groupIds());
        $this->assertEmpty($principal->talentIds());
    }
}
