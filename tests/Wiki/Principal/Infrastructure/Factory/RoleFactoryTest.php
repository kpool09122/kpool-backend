<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Infrastructure\Factory\RoleFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RoleFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(RoleFactoryInterface::class);
        $this->assertInstanceOf(RoleFactory::class, $factory);
    }

    /**
     * 正常系: 正しくRoleエンティティが作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $name = 'Administrator';
        $policies = [
            new PolicyIdentifier(StrTestHelper::generateUuid()),
        ];
        $isSystemRole = true;

        $factory = $this->app->make(RoleFactoryInterface::class);
        $role = $factory->create(
            $name,
            $policies,
            $isSystemRole,
        );

        $this->assertTrue(UuidValidator::isValid((string) $role->roleIdentifier()));
        $this->assertSame($name, $role->name());
        $this->assertSame($policies, $role->policies());
        $this->assertTrue($role->isSystemRole());
        $this->assertNotNull($role->createdAt());
    }

    /**
     * 正常系: isSystemRoleがfalseの場合も正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithNonSystemRole(): void
    {
        $name = 'Custom Role';
        $policies = [
            new PolicyIdentifier(StrTestHelper::generateUuid()),
        ];
        $isSystemRole = false;

        $factory = $this->app->make(RoleFactoryInterface::class);
        $role = $factory->create(
            $name,
            $policies,
            $isSystemRole,
        );

        $this->assertTrue(UuidValidator::isValid((string) $role->roleIdentifier()));
        $this->assertSame($name, $role->name());
        $this->assertSame($policies, $role->policies());
        $this->assertFalse($role->isSystemRole());
        $this->assertNotNull($role->createdAt());
    }

    /**
     * 正常系: 空のPoliciesでも正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithEmptyPolicies(): void
    {
        $name = 'Empty Role';
        $policies = [];
        $isSystemRole = false;

        $factory = $this->app->make(RoleFactoryInterface::class);
        $role = $factory->create(
            $name,
            $policies,
            $isSystemRole,
        );

        $this->assertTrue(UuidValidator::isValid((string) $role->roleIdentifier()));
        $this->assertSame($name, $role->name());
        $this->assertSame([], $role->policies());
        $this->assertFalse($role->isSystemRole());
    }

    /**
     * 正常系: 複数のPoliciesで正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithMultiplePolicies(): void
    {
        $name = 'Multi Policy Role';
        $policies = [
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            new PolicyIdentifier(StrTestHelper::generateUuid()),
        ];
        $isSystemRole = true;

        $factory = $this->app->make(RoleFactoryInterface::class);
        $role = $factory->create(
            $name,
            $policies,
            $isSystemRole,
        );

        $this->assertTrue(UuidValidator::isValid((string) $role->roleIdentifier()));
        $this->assertCount(2, $role->policies());
    }
}
