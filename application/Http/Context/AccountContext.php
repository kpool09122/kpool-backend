<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Account\Principal\Domain\Entity\Principal;

readonly class AccountContext
{
    public function __construct(
        private Principal $principal,
    ) {
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
