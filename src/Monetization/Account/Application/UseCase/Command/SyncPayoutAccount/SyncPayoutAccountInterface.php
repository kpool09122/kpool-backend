<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount;

interface SyncPayoutAccountInterface
{
    public function process(SyncPayoutAccountInputPort $input): void;
}
