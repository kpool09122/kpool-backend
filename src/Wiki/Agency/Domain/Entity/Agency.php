<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Entity;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

class Agency
{
    public function __construct(
        private readonly AgencyIdentifier $agencyIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Translation     $translation,
        private AgencyName               $name,
        private CEO                      $CEO,
        private ?FoundedIn               $foundedIn,
        private Description              $description,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }

    public function name(): AgencyName
    {
        return $this->name;
    }

    public function setName(AgencyName $name): void
    {
        $this->name = $name;
    }

    public function CEO(): ?CEO
    {
        return $this->CEO;
    }

    public function setCEO(CEO $CEO): void
    {
        $this->CEO = $CEO;
    }

    public function foundedIn(): ?FoundedIn
    {
        return $this->foundedIn;
    }

    public function setFoundedIn(FoundedIn $foundedIn): void
    {
        $this->foundedIn = $foundedIn;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function setDescription(Description $description): void
    {
        $this->description = $description;
    }
}
