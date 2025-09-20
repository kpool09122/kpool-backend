<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Query\GetMember;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

readonly class GetMemberInput implements GetMemberInputPort
{
    public function __construct(
        private MemberIdentifier $memberIdentifier,
        private Translation $translation,
    ) {
    }

    public function memberIdentifier(): MemberIdentifier
    {
        return $this->memberIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }
}
