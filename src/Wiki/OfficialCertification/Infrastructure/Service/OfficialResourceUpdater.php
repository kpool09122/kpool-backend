<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Infrastructure\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

readonly class OfficialResourceUpdater implements OfficialResourceUpdaterInterface
{
    public function __construct(
        private WikiRepositoryInterface $wikiRepository,
    ) {
    }

    public function markOfficial(
        ResourceType $type,
        TranslationSetIdentifier $id,
        AccountIdentifier $owner,
    ): void {
        $wikis = $this->wikiRepository->findByTranslationSetIdentifier($id);
        foreach ($wikis as $wiki) {
            if ($wiki->resourceType() !== $type || $wiki->isOfficial()) {
                continue;
            }

            $wiki->markOfficial($owner);
            $this->wikiRepository->save($wiki);
        }
    }
}
