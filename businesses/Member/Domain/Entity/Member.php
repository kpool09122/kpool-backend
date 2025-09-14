<?php

namespace Businesses\Member\Domain\Entity;

use Businesses\Member\Domain\ValueObject\Birthday;
use Businesses\Member\Domain\ValueObject\Career;
use Businesses\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Member\Domain\ValueObject\ImageLink;
use Businesses\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Member\Domain\ValueObject\MemberName;

class Member
{
    public function __construct(
        private readonly MemberIdentifier $memberIdentifier,
        private MemberName                $name,
        private ?GroupIdentifier          $groupIdentifier,
        private ?Birthday $birthday,
        private Career $career,
        private ?ImageLink $imageLink,
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

    public function setName(MemberName $name): void
    {
        $this->name = $name;
    }

    public function groupIdentifier(): ?GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function setGroupIdentifier(?GroupIdentifier $groupIdentifier): void
    {
        $this->groupIdentifier = $groupIdentifier;
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

    public function imageLink(): ?ImageLink
    {
        return $this->imageLink;
    }

    public function setImageLink(?ImageLink $imageLink): void
    {
        $this->imageLink = $imageLink;
    }
}
