<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use Source\Monetization\Account\Domain\Entity\MonetizationAccount;

class ProvisionMonetizationAccountOutput implements ProvisionMonetizationAccountOutputPort
{
    private ?MonetizationAccount $monetizationAccount = null;

    public function setMonetizationAccount(MonetizationAccount $monetizationAccount): void
    {
        $this->monetizationAccount = $monetizationAccount;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->monetizationAccount === null) {
            return [
                'monetizationAccountIdentifier' => null,
                'accountIdentifier' => null,
                'capabilities' => null,
                'stripeCustomerId' => null,
                'stripeConnectedAccountId' => null,
            ];
        }

        return [
            'monetizationAccountIdentifier' => (string) $this->monetizationAccount->monetizationAccountIdentifier(),
            'accountIdentifier' => (string) $this->monetizationAccount->accountIdentifier(),
            'capabilities' => array_map(
                static fn ($capability) => $capability->value,
                $this->monetizationAccount->capabilities(),
            ),
            'stripeCustomerId' => $this->monetizationAccount->stripeCustomerId() !== null
                ? (string) $this->monetizationAccount->stripeCustomerId()
                : null,
            'stripeConnectedAccountId' => $this->monetizationAccount->stripeConnectedAccountId() !== null
                ? (string) $this->monetizationAccount->stripeConnectedAccountId()
                : null,
        ];
    }
}
