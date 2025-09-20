<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\UseCase\Query\GetMembers;

use Businesses\Shared\ValueObject\Translation;

interface GetMembersInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function translation(): Translation;
}
