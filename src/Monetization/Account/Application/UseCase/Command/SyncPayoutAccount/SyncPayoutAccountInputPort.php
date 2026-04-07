<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount;

use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;

interface SyncPayoutAccountInputPort
{
    public function connectedAccountId(): ConnectedAccountId;

    public function externalAccountId(): ExternalAccountId;

    public function eventType(): string;

    public function bankName(): ?string;

    public function last4(): ?string;

    public function country(): ?string;

    public function currency(): ?string;

    public function accountHolderType(): ?AccountHolderType;

    public function isDefault(): bool;
}
