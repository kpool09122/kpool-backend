<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

interface DraftAgencyFactoryInterface
{
    public function create(
        ?PrincipalIdentifier      $editorIdentifier,
        Language                  $language,
        Name                      $agencyName,
        Slug                      $slug,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftAgency;
}
