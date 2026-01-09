<?php

declare(strict_types=1);

namespace Tests\Account\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\Domain\Factory\DelegationPermissionFactoryInterface;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Account\Infrastructure\Factory\DelegationPermissionFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegationPermissionFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(DelegationPermissionFactoryInterface::class);
        $this->assertInstanceOf(DelegationPermissionFactory::class, $factory);
    }

    /**
     * 正常系: 正しくDelegationPermissionエンティティが作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $targetAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $factory = $this->app->make(DelegationPermissionFactoryInterface::class);
        $delegationPermission = $factory->create(
            $identityGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
        );

        $this->assertTrue(UuidValidator::isValid((string) $delegationPermission->delegationPermissionIdentifier()));
        $this->assertSame($identityGroupIdentifier, $delegationPermission->identityGroupIdentifier());
        $this->assertSame($targetAccountIdentifier, $delegationPermission->targetAccountIdentifier());
        $this->assertSame($affiliationIdentifier, $delegationPermission->affiliationIdentifier());
        $this->assertNotNull($delegationPermission->createdAt());
    }
}
