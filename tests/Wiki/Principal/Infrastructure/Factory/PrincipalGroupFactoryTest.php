<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Infrastructure\Factory\PrincipalGroupFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalGroupFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(PrincipalGroupFactoryInterface::class);
        $this->assertInstanceOf(PrincipalGroupFactory::class, $factory);
    }

    /**
     * 正常系: 正しくPrincipalGroupエンティティが作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Test Group';
        $isDefault = true;

        $factory = $this->app->make(PrincipalGroupFactoryInterface::class);
        $principalGroup = $factory->create(
            $accountIdentifier,
            $name,
            $isDefault,
        );

        $this->assertTrue(UuidValidator::isValid((string) $principalGroup->principalGroupIdentifier()));
        $this->assertSame($accountIdentifier, $principalGroup->accountIdentifier());
        $this->assertSame($name, $principalGroup->name());
        $this->assertTrue($principalGroup->isDefault());
        $this->assertNotNull($principalGroup->createdAt());
        $this->assertSame([], $principalGroup->members());
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
        $isDefault = false;

        $factory = $this->app->make(PrincipalGroupFactoryInterface::class);
        $principalGroup = $factory->create(
            $accountIdentifier,
            $name,
            $isDefault,
        );

        $this->assertTrue(UuidValidator::isValid((string) $principalGroup->principalGroupIdentifier()));
        $this->assertSame($accountIdentifier, $principalGroup->accountIdentifier());
        $this->assertSame($name, $principalGroup->name());
        $this->assertFalse($principalGroup->isDefault());
        $this->assertNotNull($principalGroup->createdAt());
    }
}
