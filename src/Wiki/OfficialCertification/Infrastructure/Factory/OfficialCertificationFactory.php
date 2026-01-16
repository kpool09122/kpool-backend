<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Factory\OfficialCertificationFactoryInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class OfficialCertificationFactory implements OfficialCertificationFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        ResourceType $resourceType,
        ResourceIdentifier $resourceIdentifier,
        AccountIdentifier $ownerAccountIdentifier,
    ): OfficialCertification {
        return new OfficialCertification(
            new CertificationIdentifier($this->uuidGenerator->generate()),
            $resourceType,
            $resourceIdentifier,
            $ownerAccountIdentifier,
            CertificationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );
    }
}
