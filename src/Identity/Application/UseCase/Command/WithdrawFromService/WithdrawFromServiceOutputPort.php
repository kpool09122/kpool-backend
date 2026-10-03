<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\WithdrawFromService;

interface WithdrawFromServiceOutputPort
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
