<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod;

use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\Exception\CardRegistrationRejectedException;
use Source\Monetization\Account\Application\Exception\PaymentMethodNotFoundException;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

interface DeletePaymentMethodInterface
{
    /**
     * @throws UnauthorizedException
     * @throws StepUpAuthenticationRequiredException
     * @throws CardGatewayException
     * @throws CardRegistrationRejectedException
     * @throws PaymentMethodNotFoundException
     */
    public function process(DeletePaymentMethodInputPort $input, DeletePaymentMethodOutputPort $output): void;
}
