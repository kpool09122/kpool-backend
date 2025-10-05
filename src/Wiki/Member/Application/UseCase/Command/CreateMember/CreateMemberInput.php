<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\CreateMember;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\ValueObject\Birthday;
use Source\Wiki\Member\Domain\ValueObject\Career;
use Source\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Member\Domain\ValueObject\RealName;
use Source\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class CreateMemberInput implements CreateMemberInputPort
{
    /**
     * @param MemberIdentifier|null $publishedMemberIdentifier
     * @param EditorIdentifier $editorIdentifier
     * @param Translation $translation
     * @param MemberName $name
     * @param RealName $realName
     * @param GroupIdentifier[] $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param string|null $base64EncodedImage
     * @param RelevantVideoLinks $relevantVideoLinks
     * @param Principal $principal
     */
    public function __construct(
        private ?MemberIdentifier $publishedMemberIdentifier,
        private EditorIdentifier $editorIdentifier,
        private Translation $translation,
        private MemberName $name,
        private RealName $realName,
        private array $groupIdentifiers,
        private ?Birthday $birthday,
        private Career $career,
        private ?string $base64EncodedImage,
        private RelevantVideoLinks $relevantVideoLinks,
        private Principal $principal,
    ) {
    }

    public function publishedMemberIdentifier(): ?MemberIdentifier
    {
        return $this->publishedMemberIdentifier;
    }

    public function editorIdentifier(): EditorIdentifier
    {
        return $this->editorIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }

    public function name(): MemberName
    {
        return $this->name;
    }

    public function realName(): RealName
    {
        return $this->realName;
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
