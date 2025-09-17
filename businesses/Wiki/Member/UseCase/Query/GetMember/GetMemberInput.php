<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\UseCase\Query\GetMember;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;

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
