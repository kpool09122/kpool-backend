<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\CreateMember;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\ValueObject\Birthday;
use Source\Wiki\Member\Domain\ValueObject\Career;
use Source\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Member\Domain\ValueObject\RealName;
use Source\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;

interface CreateMemberInputPort
{
    public function translation(): Translation;

    public function name(): MemberName;

    public function realName(): RealName;

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array;

    public function birthday(): ?Birthday;

    public function career(): Career;

    public function base64EncodedImage(): ?string;

    public function relevantVideoLinks(): RelevantVideoLinks;
}
