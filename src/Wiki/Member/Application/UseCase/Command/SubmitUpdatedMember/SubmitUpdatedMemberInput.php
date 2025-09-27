<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\SubmitUpdatedMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

readonly class SubmitUpdatedMemberInput implements SubmitUpdatedMemberInputPort
{
    public function __construct(
        private MemberIdentifier $memberIdentifier,
    ) {
    }

    public function memberIdentifier(): MemberIdentifier
    {
        return $this->memberIdentifier;
    }
}
