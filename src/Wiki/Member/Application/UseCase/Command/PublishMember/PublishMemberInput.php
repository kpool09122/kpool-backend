<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\PublishMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

readonly class PublishMemberInput implements PublishMemberInputPort
{
    public function __construct(
        private MemberIdentifier  $memberIdentifier,
        private ?MemberIdentifier $publishedMemberIdentifier,
        private Principal         $principal,
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

    public function principal(): Principal
    {
        return $this->principal;
    }
}
