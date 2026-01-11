<?php

declare(strict_types=1);

namespace Tests\Account\IdentityGroup\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\IdentityGroup\Infrastructure\Factory\IdentityGroupFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class IdentityGroupFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(IdentityGroupFactoryInterface::class);
        $this->assertInstanceOf(IdentityGroupFactory::class, $factory);
    }

    /**
     * 正常系: 正しくIdentityGroupエンティティが作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Test Group';
        $role = AccountRole::OWNER;
        $isDefault = true;

        $factory = $this->app->make(IdentityGroupFactoryInterface::class);
        $identityGroup = $factory->create(
            $accountIdentifier,
            $name,
            $role,
            $isDefault,
        );

        $this->assertTrue(UuidValidator::isValid((string) $identityGroup->identityGroupIdentifier()));
        $this->assertSame($accountIdentifier, $identityGroup->accountIdentifier());
        $this->assertSame($name, $identityGroup->name());
        $this->assertSame($role, $identityGroup->role());
        $this->assertTrue($identityGroup->isDefault());
        $this->assertNotNull($identityGroup->createdAt());
        $this->assertSame([], $identityGroup->members());
    }

    /**
     * 正常系: isDefaultがfalseの場合も正しく作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithNonDefaultGroup(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Non Default Group';
        $role = AccountRole::MEMBER;
        $isDefault = false;

        $factory = $this->app->make(IdentityGroupFactoryInterface::class);
        $identityGroup = $factory->create(
            $accountIdentifier,
            $name,
            $role,
            $isDefault,
        );

        $this->assertTrue(UuidValidator::isValid((string) $identityGroup->identityGroupIdentifier()));
        $this->assertSame($accountIdentifier, $identityGroup->accountIdentifier());
        $this->assertSame($name, $identityGroup->name());
        $this->assertSame($role, $identityGroup->role());
        $this->assertFalse($identityGroup->isDefault());
        $this->assertNotNull($identityGroup->createdAt());
    }
}
