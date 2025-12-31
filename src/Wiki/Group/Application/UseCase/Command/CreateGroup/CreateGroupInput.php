<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\CreateGroup;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class CreateGroupInput implements CreateGroupInputPort
{
    /**
     * @param GroupIdentifier|null $publishedGroupIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param AgencyIdentifier $agencyIdentifier
     * @param Description $description
     * @param string|null $base64EncodedImage
     * @param PrincipalIdentifier $principalIdentifier
     */
    public function __construct(
        private ?GroupIdentifier    $publishedGroupIdentifier,
        private Language            $language,
        private GroupName           $name,
        private AgencyIdentifier    $agencyIdentifier,
        private Description         $description,
        private ?string             $base64EncodedImage,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function publishedGroupIdentifier(): ?GroupIdentifier
    {
        return $this->publishedGroupIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): GroupName
    {
        return $this->name;
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function base64EncodedImage(): ?string
    {
        return $this->base64EncodedImage;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
