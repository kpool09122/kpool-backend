<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Query\GetMembers;

use Source\Shared\Domain\ValueObject\Translation;

interface GetMembersInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function translation(): Translation;
}
