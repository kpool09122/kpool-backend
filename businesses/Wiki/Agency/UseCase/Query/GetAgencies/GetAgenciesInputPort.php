<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgencies;

use Businesses\Shared\ValueObject\Translation;

interface GetAgenciesInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function translation(): Translation;
}
