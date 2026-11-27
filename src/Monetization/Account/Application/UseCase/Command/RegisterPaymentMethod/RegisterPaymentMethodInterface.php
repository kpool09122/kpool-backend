<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\Exception\CardRegistrationRejectedException;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

interface RegisterPaymentMethodInterface
{
    /**
     * @throws UnauthorizedException
     * @throws StepUpAuthenticationRequiredException
     * @throws CardGatewayException
     * @throws CardRegistrationRejectedException
     */
    public function process(RegisterPaymentMethodInputPort $input, RegisterPaymentMethodOutputPort $output): void;
}
