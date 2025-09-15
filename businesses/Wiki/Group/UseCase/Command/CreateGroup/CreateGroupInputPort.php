<?php

namespace Businesses\Wiki\Group\UseCase\Command\CreateGroup;

use Businesses\Wiki\Group\Domain\ValueObject\CompanyIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\Description;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;
use Businesses\Wiki\Group\Domain\ValueObject\SongIdentifier;

interface CreateGroupInputPort
{
    public function name(): GroupName;

    public function companyIdentifier(): CompanyIdentifier;

    public function description(): Description;

    /**
     * @return list<SongIdentifier>
     */
    public function songIdentifiers(): array;

    public function base64EncodedImage(): ?string;
}
