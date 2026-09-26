<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SwitchAccount;

use Source\Account\Account\Application\Service\CurrentAccount;

interface SwitchAccountOutputPort
{
    public function setCurrentAccount(CurrentAccount $currentAccount): void;
}
