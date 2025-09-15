<?php

namespace Businesses\Group\UseCase\Query\GetGroups;

interface GetGroupsInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;
}
