<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\CreateTalent;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

interface CreateTalentInputPort
{
    public function publishedTalentIdentifier(): ?TalentIdentifier;

    public function editorIdentifier(): EditorIdentifier;

    public function translation(): Translation;

    public function name(): TalentName;

    public function realName(): RealName;

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array;

    public function birthday(): ?Birthday;

    public function career(): Career;

    public function base64EncodedImage(): ?string;

    public function relevantVideoLinks(): RelevantVideoLinks;

    public function principal(): Principal;
}
