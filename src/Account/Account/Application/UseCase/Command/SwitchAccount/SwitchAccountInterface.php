<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SwitchAccount;

interface SwitchAccountInterface
{
    public function process(SwitchAccountInputPort $input, SwitchAccountOutputPort $output): void;
}
