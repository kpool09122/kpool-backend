<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration;

use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Domain\Exception\PaymentCustomerAlreadyLinkedException;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

interface PrepareCardRegistrationInterface
{
    /**
     * @throws UnauthorizedException
     * @throws StepUpAuthenticationRequiredException
     * @throws CardGatewayException
     * @throws PaymentCustomerAlreadyLinkedException
     */
    public function process(PrepareCardRegistrationInputPort $input, PrepareCardRegistrationOutputPort $output): void;
}
