<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Repository;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

interface WikiHistoryRepositoryInterface
{
    public function findLatestSubmissionActor(DraftWikiIdentifier $draftWikiIdentifier): ?PrincipalIdentifier;

    public function save(WikiHistory $wikiHistory): void;
}
