<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Entity;

use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

class DraftTalent
{
    /**
     * @param TalentIdentifier $talentIdentifier
     * @param TalentIdentifier|null $publishedTalentIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param EditorIdentifier $editorIdentifier
     * @param Translation $translation
     * @param TalentName $name
     * @param RealName $realName
     * @param GroupIdentifier[] $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param ImagePath|null $imageLink
     * @param RelevantVideoLinks $relevantVideoLinks
     * @param ApprovalStatus $status
     */
    public function __construct(
        private readonly TalentIdentifier         $talentIdentifier,
        private ?TalentIdentifier                 $publishedTalentIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly EditorIdentifier         $editorIdentifier,
        private readonly Translation              $translation,
        private TalentName                        $name,
        private RealName                          $realName,
        private array                             $groupIdentifiers,
        private ?Birthday                         $birthday,
        private Career                            $career,
        private ?ImagePath                        $imageLink,
        private RelevantVideoLinks                $relevantVideoLinks,
        private ApprovalStatus $status,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function publishedTalentIdentifier(): ?TalentIdentifier
    {
        return $this->publishedTalentIdentifier;
    }

    public function setPublishedTalentIdentifier(TalentIdentifier $talentIdentifier): void
    {
        $this->publishedTalentIdentifier = $talentIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function editorIdentifier(): EditorIdentifier
    {
        return $this->editorIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }

    public function name(): TalentName
    {
        return $this->name;
    }

    public function setName(TalentName $name): void
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

    public function status(): ApprovalStatus
    {
        return $this->status;
    }

    public function setStatus(ApprovalStatus $status): void
    {
        $this->status = $status;
    }
}
