<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Infrastructure\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class OfficialResourceUpdater implements OfficialResourceUpdaterInterface
{
    public function __construct(
        private WikiRepositoryInterface $wikiRepository,
    ) {
    }

    public function markOfficial(
        ResourceType $type,
        ResourceIdentifier $id,
        AccountIdentifier $owner,
    ): void {
        $wiki = $this->wikiRepository->findById(new WikiIdentifier((string) $id));
        if ($wiki === null || $wiki->isOfficial()) {
            return;
        }
        $wiki->markOfficial($owner);
        $this->wikiRepository->save($wiki);
    }
}
