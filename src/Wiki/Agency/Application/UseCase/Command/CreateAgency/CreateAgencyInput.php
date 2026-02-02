<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\FoundedIn;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

readonly class CreateAgencyInput implements CreateAgencyInputPort
{
    /**
     * @param ?AgencyIdentifier $publishedAgencyIdentifier
     * @param Language $language
     * @param Name $name
     * @param Slug $slug
     * @param CEO $CEO
     * @param ?FoundedIn $foundedIn
     * @param Description $description
     * @param PrincipalIdentifier $principalIdentifier
     */
    public function __construct(
        private ?AgencyIdentifier   $publishedAgencyIdentifier,
        private Language            $language,
        private Name                $name,
        private Slug                $slug,
        private CEO                 $CEO,
        private ?FoundedIn          $foundedIn,
        private Description         $description,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function publishedAgencyIdentifier(): ?AgencyIdentifier
    {
        return $this->publishedAgencyIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function slug(): Slug
    {
        return $this->slug;
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

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
