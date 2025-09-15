<?php

namespace Businesses\Wiki\Member\UseCase\Query\GetMembers;

interface GetMembersInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;
}
