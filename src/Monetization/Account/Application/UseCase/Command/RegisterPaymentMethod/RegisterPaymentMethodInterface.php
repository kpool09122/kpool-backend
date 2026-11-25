<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Application\Exception\PaymentMethodAlreadyExistsException;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

interface RegisterPaymentMethodInterface
{
    /**
     * @throws UnauthorizedException
     * @throws PaymentMethodAlreadyExistsException
     */
    public function process(RegisterPaymentMethodInputPort $input, RegisterPaymentMethodOutputPort $output): void;
}
