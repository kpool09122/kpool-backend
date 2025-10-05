<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\EditGroup;

use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

interface EditGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function name(): GroupName;

    public function agencyIdentifier(): AgencyIdentifier;

    public function description(): Description;

    /**
     * @return list<SongIdentifier>
     */
    public function songIdentifiers(): array;

    public function base64EncodedImage(): ?string;

    public function principal(): Principal;
}
