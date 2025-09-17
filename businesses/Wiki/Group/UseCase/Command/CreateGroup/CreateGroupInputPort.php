<?php

declare(strict_types=1);

namespace Businesses\Wiki\Group\UseCase\Command\CreateGroup;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\Description;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;
use Businesses\Wiki\Group\Domain\ValueObject\SongIdentifier;

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
