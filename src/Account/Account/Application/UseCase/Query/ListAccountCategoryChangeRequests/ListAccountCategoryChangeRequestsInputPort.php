<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests;

use Source\Account\Principal\Domain\Entity\Principal;

interface ListAccountCategoryChangeRequestsInputPort
{
    public function principal(): Principal;

    public function status(): ?string;

    public function requestedAccountCategory(): ?string;

    public function perPage(): int;

    public function page(): int;
}
