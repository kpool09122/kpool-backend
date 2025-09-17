<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\Domain\Entity;

use Businesses\Shared\ValueObject\ImagePath;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Member\Domain\ValueObject\Birthday;
use Businesses\Wiki\Member\Domain\ValueObject\Career;
use Businesses\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;
use Businesses\Wiki\Member\Domain\ValueObject\RealName;
use Businesses\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;

class Member
{
    /**
     * @param MemberIdentifier $memberIdentifier
     * @param MemberName $name
     * @param GroupIdentifier[] $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param ImagePath|null $imageLink
     * @param RelevantVideoLinks $relevantVideoLinks
     */
    public function __construct(
        private readonly MemberIdentifier $memberIdentifier,
        private readonly Translation $translation,
        private MemberName $name,
        private RealName $realName,
        private array $groupIdentifiers,
        private ?Birthday $birthday,
        private Career $career,
        private ?ImagePath $imageLink,
        private RelevantVideoLinks $relevantVideoLinks,
    ) {
    }

    public function memberIdentifier(): MemberIdentifier
    {
        return $this->memberIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }

    public function name(): MemberName
    {
        return $this->name;
    }

    public function setName(MemberName $name): void
    {
        $this->name = $name;
    }

    public function realName(): RealName
    {
        return $this->realName;
    }

    public function setRealName(RealName $realName): void
    {
        $this->realName = $realName;
    }

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    /**
     * @param GroupIdentifier[] $groupIdentifiers
     * @return void
     */
    public function setGroupIdentifiers(array $groupIdentifiers): void
    {
        $this->groupIdentifiers = $groupIdentifiers;
    }

    public function birthday(): ?Birthday
    {
        return $this->birthday;
    }

    public function setBirthday(?Birthday $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function career(): Career
    {
        return $this->career;
    }

    public function setCareer(Career $career): void
    {
        $this->career = $career;
    }

    public function imageLink(): ?ImagePath
    {
        return $this->imageLink;
    }

    public function setImageLink(?ImagePath $imageLink): void
    {
        $this->imageLink = $imageLink;
    }

    /**
     * @return RelevantVideoLinks
     */
    public function relevantVideoLinks(): RelevantVideoLinks
    {
        return $this->relevantVideoLinks;
    }

    /**
     * @param RelevantVideoLinks $relevantVideoLinks
     * @return void
     */
    public function setRelevantVideoLinks(RelevantVideoLinks $relevantVideoLinks): void
    {
        $this->relevantVideoLinks = $relevantVideoLinks;
    }
}
