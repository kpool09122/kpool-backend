<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\MergeWiki;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface MergeWikiInputPort
{
    public function wikiIdentifier(): DraftWikiIdentifier;

    public function basic(): BasicInterface;

    public function sections(): SectionContentCollection;

    public function themeColor(): ?Color;

    public function principalIdentifier(): PrincipalIdentifier;

    public function resourceType(): ResourceType;

    public function mergedAt(): DateTimeImmutable;

    public function agencyIdentifier(): ?WikiIdentifier;

    /** @return WikiIdentifier[] */
    public function groupIdentifiers(): array;

    /** @return WikiIdentifier[] */
    public function talentIdentifiers(): array;
}
