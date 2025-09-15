<?php

namespace Businesses\Wiki\Member\Domain\Entity;

use Businesses\Shared\ValueObject\ImagePath;
use Businesses\Wiki\Member\Domain\ValueObject\Birthday;
use Businesses\Wiki\Member\Domain\ValueObject\Career;
use Businesses\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;

class Member
{
    public function __construct(
        private readonly MemberIdentifier $memberIdentifier,
        private MemberName                $name,
        private ?GroupIdentifier          $groupIdentifier,
        private ?Birthday                 $birthday,
        private Career                    $career,
        private ?ImagePath                $imageLink,
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

    public function imageLink(): ?ImagePath
    {
        return $this->imageLink;
    }

    public function setImageLink(?ImagePath $imageLink): void
    {
        $this->imageLink = $imageLink;
    }
}
