<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\RejectMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

readonly class RejectMemberInput implements RejectMemberInputPort
{
    public function __construct(
        private MemberIdentifier  $memberIdentifier,
        private Principal $principal,
    ) {
    }

    public function memberIdentifier(): MemberIdentifier
    {
        return $this->memberIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
