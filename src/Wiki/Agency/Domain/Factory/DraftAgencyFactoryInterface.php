<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface DraftAgencyFactoryInterface
{
    public function create(
        PrincipalIdentifier       $editorIdentifier,
        Language                  $language,
        AgencyName                $agencyName,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftAgency;
}
