<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest;

interface RejectAccountCategoryChangeRequestInterface
{
    public function process(RejectAccountCategoryChangeRequestInputPort $input, RejectAccountCategoryChangeRequestOutputPort $output): void;
}
