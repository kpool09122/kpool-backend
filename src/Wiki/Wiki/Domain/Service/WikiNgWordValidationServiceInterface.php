<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Service;

use Source\Wiki\Wiki\Domain\Exception\NgWordDetectedException;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;

interface WikiNgWordValidationServiceInterface
{
    /** @throws NgWordDetectedException */
    public function validate(
        BasicInterface $basic,
        SectionContentCollection $sections,
        ?SeoTitle $title,
        ?MetaDescription $metaDescription,
        ?SeoKeywords $keywords,
    ): void;
}
