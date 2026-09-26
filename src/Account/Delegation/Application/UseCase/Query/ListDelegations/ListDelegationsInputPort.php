<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Query\ListDelegations;

use Source\Account\Principal\Domain\Entity\Principal;

interface ListDelegationsInputPort
{
    public function principal(): Principal;

    public function status(): ?string;

    public function viewerRole(): ?string;

    public function perPage(): int;

    public function page(): int;
}
