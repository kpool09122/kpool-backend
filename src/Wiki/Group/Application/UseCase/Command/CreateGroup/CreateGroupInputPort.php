<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\CreateGroup;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;

interface CreateGroupInputPort
{
    public function translation(): Translation;

    public function name(): GroupName;

    public function agencyIdentifier(): AgencyIdentifier;

    public function description(): Description;

    /**
     * @return list<SongIdentifier>
     */
    public function songIdentifiers(): array;

    public function base64EncodedImage(): ?string;
}
