<?php

namespace Businesses\Wiki\Member\UseCase\Command\CreateMember;

use Businesses\Wiki\Member\Domain\ValueObject\Birthday;
use Businesses\Wiki\Member\Domain\ValueObject\Career;
use Businesses\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;
use Businesses\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;

interface CreateMemberInputPort
{
    public function name(): MemberName;

    public function groupIdentifier(): ?GroupIdentifier;

    public function birthday(): ?Birthday;

    public function career(): Career;

    public function base64EncodedImage(): ?string;

    public function relevantVideoLinks(): RelevantVideoLinks;
}
