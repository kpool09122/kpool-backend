<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest;

interface ApproveAccountTypeChangeRequestInterface
{
    public function process(ApproveAccountTypeChangeRequestInputPort $input, ApproveAccountTypeChangeRequestOutputPort $output): void;
}
