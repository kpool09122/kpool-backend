<?php

namespace Businesses\Wiki\Member\UseCase\Command\CreateMember;

use Businesses\Wiki\Member\Domain\ValueObject\Birthday;
use Businesses\Wiki\Member\Domain\ValueObject\Career;
use Businesses\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;
use Businesses\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;

class CreateMemberInput implements CreateMemberInputPort
{
    /**
     * @param MemberName $name
     * @param GroupIdentifier|null $groupIdentifier
     * @param Birthday|null $birthday
     * @param Career $career
     * @param string|null $base64EncodedImage
     * @param RelevantVideoLinks $relevantVideoLinks
     */
    public function __construct(
        private MemberName $name,
        private ?GroupIdentifier $groupIdentifier,
        private ?Birthday $birthday,
        private Career $career,
        private ?string $base64EncodedImage,
        private RelevantVideoLinks $relevantVideoLinks,
    ) {
    }

    public function name(): MemberName
    {
        return $this->name;
    }

    public function groupIdentifier(): ?GroupIdentifier
    {
        return $this->groupIdentifier;
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
}
