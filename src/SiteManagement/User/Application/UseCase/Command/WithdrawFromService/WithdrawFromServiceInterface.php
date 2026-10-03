<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\WithdrawFromService;

interface WithdrawFromServiceInterface
{
    public function process(WithdrawFromServiceInputPort $input, WithdrawFromServiceOutputPort $output): void;
}
