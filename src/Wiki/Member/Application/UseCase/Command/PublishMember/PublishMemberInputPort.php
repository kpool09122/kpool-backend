<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\PublishMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface PublishMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function publishedMemberIdentifier(): ?MemberIdentifier;
}
