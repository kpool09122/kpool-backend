<?php

namespace Businesses\Member\UseCase\Command\EditMember;

use Businesses\Member\Domain\ValueObject\Birthday;
use Businesses\Member\Domain\ValueObject\Career;
use Businesses\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Member\Domain\ValueObject\MemberName;

readonly class EditMemberInput implements EditMemberInputPort
{
    public function __construct(
        private MemberIdentifier $memberIdentifier,
        private MemberName $name,
        private ?GroupIdentifier $groupIdentifier,
        private ?Birthday $birthday,
        private Career $career,
        private ?string $base64EncodedImage,
    ) {
    }

    public function memberIdentifier(): MemberIdentifier
    {
        return $this->memberIdentifier;
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
}
