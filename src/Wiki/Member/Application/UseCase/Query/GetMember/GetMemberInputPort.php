<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Query\GetMember;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface GetMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function translation(): Translation;
}
