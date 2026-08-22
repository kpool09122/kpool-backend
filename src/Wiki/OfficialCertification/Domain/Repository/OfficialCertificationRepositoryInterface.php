<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface OfficialCertificationRepositoryInterface
{
    public function save(OfficialCertification $entity): void;

    public function findById(CertificationIdentifier $id): ?OfficialCertification;

    public function findByResource(ResourceType $type, TranslationSetIdentifier $id): ?OfficialCertification;

    public function existsPending(ResourceType $type, TranslationSetIdentifier $id): bool;
}
