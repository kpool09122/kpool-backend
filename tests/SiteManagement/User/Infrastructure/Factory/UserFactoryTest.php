<?php

declare(strict_types=1);

namespace Tests\SiteManagement\User\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\Factory\UserFactoryInterface;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    /**
     * 正常系: 正しくUser Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $factory = $this->app->make(UserFactoryInterface::class);
        $user = $factory->create($identityIdentifier);

        $this->assertTrue(UuidValidator::isValid((string)$user->userIdentifier()));
        $this->assertSame($identityIdentifier, $user->identityIdentifier());
        $this->assertSame(Role::NONE, $user->role());
    }
}
