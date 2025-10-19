<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\EditTalent;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

readonly class EditTalentInput implements EditTalentInputPort
{
    /**
     * @param TalentIdentifier $talentIdentifier
     * @param TalentName $name
     * @param RealName $realName
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param GroupIdentifier[] $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param string|null $base64EncodedImage
     * @param RelevantVideoLinks $relevantVideoLinks
     * @param Principal $principal
     */
    public function __construct(
        private TalentIdentifier   $talentIdentifier,
        private TalentName         $name,
        private RealName           $realName,
        private ?AgencyIdentifier $agencyIdentifier,
        private array              $groupIdentifiers,
        private ?Birthday          $birthday,
        private Career             $career,
        private ?string            $base64EncodedImage,
        private RelevantVideoLinks $relevantVideoLinks,
        private Principal          $principal,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function name(): TalentName
    {
        return $this->name;
    }

    public function realName(): RealName
    {
        return $this->realName;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    public function birthday(): ?Birthday
    {
        return $this->birthday;
    }

    public function career(): Career
    {
        return $this->career;
    }

    public function base64EncodedImage(): ?string
    {
        return $this->base64EncodedImage;
    }

    public function relevantVideoLinks(): RelevantVideoLinks
    {
        return $this->relevantVideoLinks;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
