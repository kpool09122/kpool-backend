<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount;

use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;

readonly class SyncPayoutAccountInput implements SyncPayoutAccountInputPort
{
    public function __construct(
        private ConnectedAccountId $connectedAccountId,
        private ExternalAccountId $externalAccountId,
        private string $eventType,
        private ?string $bankName = null,
        private ?string $last4 = null,
        private ?string $country = null,
        private ?string $currency = null,
        private ?AccountHolderType $accountHolderType = null,
        private bool $isDefault = false,
    ) {
    }

    public function connectedAccountId(): ConnectedAccountId
    {
        return $this->connectedAccountId;
    }

    public function externalAccountId(): ExternalAccountId
    {
        return $this->externalAccountId;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function bankName(): ?string
    {
        return $this->bankName;
    }

    public function last4(): ?string
    {
        return $this->last4;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function currency(): ?string
    {
        return $this->currency;
    }

    public function accountHolderType(): ?AccountHolderType
    {
        return $this->accountHolderType;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }
}
