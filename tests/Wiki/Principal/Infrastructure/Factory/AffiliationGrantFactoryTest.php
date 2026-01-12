<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Principal\Domain\Factory\AffiliationGrantFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Principal\Infrastructure\Factory\AffiliationGrantFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationGrantFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(AffiliationGrantFactoryInterface::class);
        $this->assertInstanceOf(AffiliationGrantFactory::class, $factory);
    }

    /**
     * 正常系: TALENT_SIDEのAffiliationGrantが正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateTalentSide(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $type = AffiliationGrantType::TALENT_SIDE;

        $factory = $this->app->make(AffiliationGrantFactoryInterface::class);
        $affiliationGrant = $factory->create(
            $affiliationIdentifier,
            $policyIdentifier,
            $roleIdentifier,
            $principalGroupIdentifier,
            $type,
        );

        $this->assertTrue(UuidValidator::isValid((string) $affiliationGrant->affiliationGrantIdentifier()));
        $this->assertSame($affiliationIdentifier, $affiliationGrant->affiliationIdentifier());
        $this->assertSame($policyIdentifier, $affiliationGrant->policyIdentifier());
        $this->assertSame($roleIdentifier, $affiliationGrant->roleIdentifier());
        $this->assertSame($principalGroupIdentifier, $affiliationGrant->principalGroupIdentifier());
        $this->assertSame($type, $affiliationGrant->type());
        $this->assertNotNull($affiliationGrant->createdAt());
    }

    /**
     * 正常系: AGENCY_SIDEのAffiliationGrantが正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateAgencySide(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $type = AffiliationGrantType::AGENCY_SIDE;

        $factory = $this->app->make(AffiliationGrantFactoryInterface::class);
        $affiliationGrant = $factory->create(
            $affiliationIdentifier,
            $policyIdentifier,
            $roleIdentifier,
            $principalGroupIdentifier,
            $type,
        );

        $this->assertTrue(UuidValidator::isValid((string) $affiliationGrant->affiliationGrantIdentifier()));
        $this->assertSame($affiliationIdentifier, $affiliationGrant->affiliationIdentifier());
        $this->assertSame($policyIdentifier, $affiliationGrant->policyIdentifier());
        $this->assertSame($roleIdentifier, $affiliationGrant->roleIdentifier());
        $this->assertSame($principalGroupIdentifier, $affiliationGrant->principalGroupIdentifier());
        $this->assertSame($type, $affiliationGrant->type());
        $this->assertNotNull($affiliationGrant->createdAt());
    }
}
