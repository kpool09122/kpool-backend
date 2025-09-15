<?php

namespace Businesses\Member\UseCase\Command\EditMember;

use Businesses\Member\Domain\ValueObject\Birthday;
use Businesses\Member\Domain\ValueObject\Career;
use Businesses\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Member\Domain\ValueObject\MemberName;

interface EditMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function name(): MemberName;

    public function groupIdentifier(): ?GroupIdentifier;

    public function birthday(): ?Birthday;

    public function career(): ?Career;

    public function base64EncodedImage(): ?string;
}
