<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests;

use Source\Account\Principal\Domain\Entity\Principal;

interface ListAccountSnapshotChangeRequestsInputPort
{
    public function principal(): Principal;

    public function status(): ?string;

    public function perPage(): int;

    public function page(): int;
}
