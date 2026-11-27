<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration;

use Source\Monetization\Account\Application\Service\CardRegistrationPreparation;

interface PrepareCardRegistrationOutputPort
{
    public function setPreparation(CardRegistrationPreparation $preparation): void;
}
