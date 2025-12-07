<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\UserIdentifier;

readonly class SettlementAccount
{
    public function __construct(
        private SettlementAccountIdentifier $settlementAccountIdentifier,
        private UserIdentifier $ownerIdentifier,
        private string $bankName,
        private string $accountNumberLast4,
        private Currency $currency,
        private bool $verified
    ) {
        $this->assertBankName($bankName);
        $this->assertAccountNumber($accountNumberLast4);
    }

    public function settlementAccountIdentifier(): SettlementAccountIdentifier
    {
        return $this->settlementAccountIdentifier;
    }

    public function ownerIdentifier(): UserIdentifier
    {
        return $this->ownerIdentifier;
    }

    public function bankName(): string
    {
        return $this->bankName;
    }

    public function accountNumberLast4(): string
    {
        return $this->accountNumberLast4;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    private function assertBankName(string $bankName): void
    {
        if (trim($bankName) === '') {
            throw new InvalidArgumentException('Bank name must not be empty.');
        }
    }

    private function assertAccountNumber(string $accountNumberLast4): void
    {
        if (! preg_match('/^[0-9]{4}$/', $accountNumberLast4)) {
            throw new InvalidArgumentException('Account number last 4 must be numeric.');
        }
    }
}
