<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

interface RegisterPaymentMethodInterface
{
    public function process(RegisterPaymentMethodInputPort $input, RegisterPaymentMethodOutputPort $output): void;
}
