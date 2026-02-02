<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

interface AgencyFactoryInterface
{
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Slug                     $slug,
        Language                 $language,
        Name                     $agencyName,
    ): Agency;
}
