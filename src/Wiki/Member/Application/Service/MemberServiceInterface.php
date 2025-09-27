<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\Service;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface MemberServiceInterface
{
    public function existsApprovedButNotTranslatedMember(
        MemberIdentifier $memberIdentifier,
        MemberIdentifier $publishedMemberIdentifier,
    ): bool;

    public function translateMember(
        Member  $member,
        Translation $translation,
    ): DraftMember;
}
