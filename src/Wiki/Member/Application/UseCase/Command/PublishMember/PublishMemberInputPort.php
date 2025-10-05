<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\PublishMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

interface PublishMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function publishedMemberIdentifier(): ?MemberIdentifier;

    public function principal(): Principal;
}
