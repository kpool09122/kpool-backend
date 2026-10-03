<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\WithdrawFromService;

class WithdrawFromServiceOutput implements WithdrawFromServiceOutputPort
{
    /** @return array{} */
    public function toArray(): array
    {
        return [];
    }
}
