<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Domain\Factory\OfficialCertificationFactoryInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\OfficialCertification\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\OfficialCertification\Infrastructure\Factory\OfficialCertificationFactory;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class OfficialCertificationFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(OfficialCertificationFactoryInterface::class);
        $this->assertInstanceOf(OfficialCertificationFactory::class, $factory);
    }

    /**
     * 正常系: 正しくOfficialCertificationエンティティが作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $resourceType = ResourceType::GROUP;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $factory = $this->app->make(OfficialCertificationFactoryInterface::class);
        $certification = $factory->create(
            $resourceType,
            $resourceIdentifier,
            $ownerAccountIdentifier,
        );

        $this->assertTrue(UuidValidator::isValid((string) $certification->certificationIdentifier()));
        $this->assertSame($resourceType, $certification->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $certification->resourceIdentifier());
        $this->assertSame((string) $ownerAccountIdentifier, (string) $certification->ownerAccountIdentifier());
        $this->assertTrue($certification->status()->isPending());
        $this->assertSame(CertificationStatus::PENDING, $certification->status());
        $this->assertNotNull($certification->requestedAt());
        $this->assertNull($certification->approvedAt());
        $this->assertNull($certification->rejectedAt());
    }
}
