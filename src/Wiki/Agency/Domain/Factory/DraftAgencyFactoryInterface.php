<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface DraftAgencyFactoryInterface
{
    public function create(
        EditorIdentifier $editorIdentifier,
        Translation $translation,
        AgencyName $agencyName,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftAgency;
}
