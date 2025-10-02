<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\ApproveMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

readonly class ApproveMemberInput implements ApproveMemberInputPort
{
    public function __construct(
        private MemberIdentifier  $memberIdentifier,
        private ?MemberIdentifier $publishedMemberIdentifier,
    ) {
    }

    public function memberIdentifier(): MemberIdentifier
    {
        return $this->memberIdentifier;
    }

    public function publishedMemberIdentifier(): ?MemberIdentifier
    {
        return $this->publishedMemberIdentifier;
    }
}
