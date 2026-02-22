<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount;

interface SyncPayoutAccountInputPort
{
    public function connectedAccountId(): string;

    public function externalAccountId(): string;

    public function eventType(): string;

    public function bankName(): ?string;

    public function last4(): ?string;

    public function country(): ?string;

    public function currency(): ?string;

    public function accountHolderType(): ?string;

    public function isDefault(): bool;
}
