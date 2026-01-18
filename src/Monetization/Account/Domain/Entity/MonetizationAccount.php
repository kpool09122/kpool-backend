<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Entity;

use DomainException;
use Source\Monetization\Account\Domain\Exception\CapabilityAlreadyGrantedException;
use Source\Monetization\Account\Domain\Exception\CapabilityNotGrantedException;
use Source\Monetization\Account\Domain\ValueObject\BillingAddress;
use Source\Monetization\Account\Domain\ValueObject\BillingContact;
use Source\Monetization\Account\Domain\ValueObject\BillingMethod;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\StripeCustomerId;
use Source\Monetization\Account\Domain\ValueObject\TaxInfo;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class MonetizationAccount
{
    /**
     * @param MonetizationAccountIdentifier $monetizationAccountIdentifier
     * @param AccountIdentifier $accountIdentifier
     * @param Capability[] $capabilities
     * @param StripeCustomerId|null $stripeCustomerId
     * @param StripeConnectedAccountId|null $stripeConnectedAccountId
     * @param BillingAddress|null $billingAddress
     * @param BillingContact|null $billingContact
     * @param BillingMethod|null $billingMethod
     * @param TaxInfo|null $taxInfo
     */
    public function __construct(
        private readonly MonetizationAccountIdentifier $monetizationAccountIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private array $capabilities,
        private ?StripeCustomerId $stripeCustomerId,
        private ?StripeConnectedAccountId $stripeConnectedAccountId,
        private ?BillingAddress $billingAddress = null,
        private ?BillingContact $billingContact = null,
        private ?BillingMethod $billingMethod = null,
        private ?TaxInfo $taxInfo = null,
    ) {
    }

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->monetizationAccountIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    /**
     * @return Capability[]
     */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    public function stripeCustomerId(): ?StripeCustomerId
    {
        return $this->stripeCustomerId;
    }

    public function stripeConnectedAccountId(): ?StripeConnectedAccountId
    {
        return $this->stripeConnectedAccountId;
    }

    public function hasCapability(Capability $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    public function canPurchase(): bool
    {
        return $this->hasCapability(Capability::PURCHASE);
    }

    public function canSell(): bool
    {
        return $this->hasCapability(Capability::SELL);
    }

    public function canReceivePayout(): bool
    {
        return $this->hasCapability(Capability::RECEIVE_PAYOUT);
    }

    /**
     * @throws CapabilityAlreadyGrantedException
     */
    public function grantCapability(Capability $capability): void
    {
        if ($this->hasCapability($capability)) {
            throw new CapabilityAlreadyGrantedException($capability);
        }

        $this->capabilities[] = $capability;
    }

    /**
     * @throws CapabilityNotGrantedException
     */
    public function revokeCapability(Capability $capability): void
    {
        if (! $this->hasCapability($capability)) {
            throw new CapabilityNotGrantedException($capability);
        }

        $this->capabilities = array_values(
            array_filter(
                $this->capabilities,
                static fn (Capability $c) => $c !== $capability
            )
        );
    }

    /**
     * @throws DomainException
     */
    public function linkStripeCustomer(StripeCustomerId $stripeCustomerId): void
    {
        if ($this->stripeCustomerId !== null) {
            throw new DomainException('Stripe Customer already linked.');
        }

        $this->stripeCustomerId = $stripeCustomerId;
    }

    /**
     * @throws DomainException
     */
    public function linkStripeConnectedAccount(StripeConnectedAccountId $stripeConnectedAccountId): void
    {
        if ($this->stripeConnectedAccountId !== null) {
            throw new DomainException('Stripe Connected Account already linked.');
        }

        $this->stripeConnectedAccountId = $stripeConnectedAccountId;
    }

    /**
     * 購入操作を実行可能か検証
     *
     * @throws DomainException
     */
    public function assertCanMakePurchase(): void
    {
        if (! $this->canPurchase()) {
            throw new DomainException('Account does not have purchase capability.');
        }

        if ($this->stripeCustomerId === null) {
            throw new DomainException('Stripe Customer is not linked.');
        }
    }

    /**
     * 販売操作を実行可能か検証
     *
     * @throws DomainException
     */
    public function assertCanSell(): void
    {
        if (! $this->canSell()) {
            throw new DomainException('Account does not have sell capability.');
        }

        if ($this->stripeConnectedAccountId === null) {
            throw new DomainException('Stripe Connected Account is not linked.');
        }
    }

    /**
     * 出金受取が可能か検証
     *
     * @throws DomainException
     */
    public function assertCanReceivePayout(): void
    {
        if (! $this->canReceivePayout()) {
            throw new DomainException('Account does not have payout capability.');
        }

        if ($this->stripeConnectedAccountId === null) {
            throw new DomainException('Stripe Connected Account is not linked.');
        }
    }

    public function billingAddress(): ?BillingAddress
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?BillingAddress $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function billingContact(): ?BillingContact
    {
        return $this->billingContact;
    }

    public function setBillingContact(?BillingContact $billingContact): void
    {
        $this->billingContact = $billingContact;
    }

    public function billingMethod(): ?BillingMethod
    {
        return $this->billingMethod;
    }

    public function setBillingMethod(?BillingMethod $billingMethod): void
    {
        $this->billingMethod = $billingMethod;
    }

    public function taxInfo(): ?TaxInfo
    {
        return $this->taxInfo;
    }

    public function setTaxInfo(?TaxInfo $taxInfo): void
    {
        $this->taxInfo = $taxInfo;
    }

    /**
     * 請求情報をまとめて設定
     */
    public function setBillingInfo(
        ?BillingAddress $billingAddress,
        ?BillingContact $billingContact,
        ?BillingMethod $billingMethod,
        ?TaxInfo $taxInfo,
    ): void {
        $this->billingAddress = $billingAddress;
        $this->billingContact = $billingContact;
        $this->billingMethod = $billingMethod;
        $this->taxInfo = $taxInfo;
    }
}
