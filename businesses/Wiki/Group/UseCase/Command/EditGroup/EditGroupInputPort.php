<?php

namespace Businesses\Wiki\Group\UseCase\Command\EditGroup;

use Businesses\Wiki\Group\Domain\ValueObject\CompanyIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\Description;
use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;
use Businesses\Wiki\Group\Domain\ValueObject\SongIdentifier;

interface EditGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function name(): GroupName;

    public function companyIdentifier(): CompanyIdentifier;

    public function description(): Description;

    /**
     * @return list<SongIdentifier>
     */
    public function songIdentifiers(): array;

    public function base64EncodedImage(): ?string;
}
