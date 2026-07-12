<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiFontStyle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface CreateWikiInputPort
{
    public function publishedWikiIdentifier(): ?WikiIdentifier;

    public function language(): Language;

    public function resourceType(): ResourceType;

    public function basic(): BasicInterface;

    public function sections(): SectionContentCollection;

    public function themeColor(): ?Color;

    public function fontStyle(): ?WikiFontStyle;

    public function imageIdentifier(): ?ImageIdentifier;

    public function title(): ?SeoTitle;

    public function metaDescription(): ?MetaDescription;

    public function keywords(): ?SeoKeywords;

    public function slug(): Slug;

    public function principalIdentifier(): PrincipalIdentifier;

    public function agencyIdentifier(): ?WikiIdentifier;

    /** @return WikiIdentifier[] */
    public function groupIdentifiers(): array;

    /** @return WikiIdentifier[] */
    public function talentIdentifiers(): array;
}
