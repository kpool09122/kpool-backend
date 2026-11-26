<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration;

use LogicException;
use Source\Monetization\Account\Application\Service\CardRegistrationPreparation;

class PrepareCardRegistrationOutput implements PrepareCardRegistrationOutputPort
{
    private ?CardRegistrationPreparation $preparation = null;

    public function setPreparation(CardRegistrationPreparation $preparation): void
    {
        $this->preparation = $preparation;
    }

    /** @return array{setupIntentIdentifier: string, clientSecret: string} */
    public function toArray(): array
    {
        $preparation = $this->preparation ?? throw new LogicException('Card registration has not been prepared.');

        return ['setupIntentIdentifier' => (string) $preparation->identifier, 'clientSecret' => $preparation->clientSecret];
    }
}
