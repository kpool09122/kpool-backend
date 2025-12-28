<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class CreateAgencyInput implements CreateAgencyInputPort
{
    /**
     * @param ?AgencyIdentifier $publishedAgencyIdentifier
     * @param EditorIdentifier $editorIdentifier
     * @param Language $language
     * @param AgencyName $name
     * @param CEO $CEO
     * @param ?FoundedIn $foundedIn
     * @param Description $description
     * @param Principal $principal
     */
    public function __construct(
        private ?AgencyIdentifier $publishedAgencyIdentifier,
        private EditorIdentifier  $editorIdentifier,
        private Language          $language,
        private AgencyName        $name,
        private CEO               $CEO,
        private ?FoundedIn        $foundedIn,
        private Description       $description,
        private Principal         $principal,
    ) {
    }

    public function publishedAgencyIdentifier(): ?AgencyIdentifier
    {
        return $this->publishedAgencyIdentifier;
    }

    public function editorIdentifier(): EditorIdentifier
    {
        return $this->editorIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): AgencyName
    {
        return $this->name;
    }

    public function CEO(): CEO
    {
        return $this->CEO;
    }

    public function foundedIn(): ?FoundedIn
    {
        return $this->foundedIn;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
