<?php

namespace Businesses\Wiki\Member\UseCase\Command\EditMember;

use Businesses\Wiki\Member\Domain\ValueObject\Birthday;
use Businesses\Wiki\Member\Domain\ValueObject\Career;
use Businesses\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;

interface EditMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function name(): MemberName;

    public function groupIdentifier(): ?GroupIdentifier;

    public function birthday(): ?Birthday;

    public function career(): ?Career;

    public function base64EncodedImage(): ?string;
}
