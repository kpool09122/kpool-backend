<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations;

use Source\Account\Principal\Domain\Entity\Principal;

interface ListAffiliationsInputPort
{
    public function principal(): Principal;

    public function status(): ?string;

    public function viewerRole(): ?string;

    public function perPage(): int;

    public function page(): int;
}
