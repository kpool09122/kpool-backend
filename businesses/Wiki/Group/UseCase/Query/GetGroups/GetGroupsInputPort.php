<?php

namespace Businesses\Wiki\Group\UseCase\Query\GetGroups;

use Businesses\Shared\ValueObject\Translation;

interface GetGroupsInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function translation(): Translation;
}
