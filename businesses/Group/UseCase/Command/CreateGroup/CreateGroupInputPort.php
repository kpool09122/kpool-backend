<?php

namespace Businesses\Group\UseCase\Command\CreateGroup;

use Businesses\Group\Domain\ValueObject\CompanyIdentifier;
use Businesses\Group\Domain\ValueObject\Description;
use Businesses\Group\Domain\ValueObject\GroupName;
use Businesses\Group\Domain\ValueObject\SongIdentifier;

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
