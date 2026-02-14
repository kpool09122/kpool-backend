<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Entity;

use Source\Monetization\Account\Domain\Exception\CapabilityAlreadyGrantedException;
use Source\Monetization\Account\Domain\Exception\CapabilityNotGrantedException;
use Source\Monetization\Account\Domain\Exception\ConnectedAccountAlreadyLinkedException;
use Source\Monetization\Account\Domain\Exception\ConnectedAccountNotLinkedException;
use Source\Monetization\Account\Domain\Exception\PaymentCustomerAlreadyLinkedException;
use Source\Monetization\Account\Domain\Exception\PaymentCustomerNotLinkedException;
use Source\Monetization\Account\Domain\ValueObject\BillingAddress;
use Source\Monetization\Account\Domain\ValueObject\BillingContact;
use Source\Monetization\Account\Domain\ValueObject\BillingMethod;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Account\Domain\ValueObject\TaxInfo;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class MonetizationAccount
{
    /**
     * @param MonetizationAccountIdentifier $monetizationAccountIdentifier
     * @param AccountIdentifier $accountIdentifier
     * @param Capability[] $capabilities
     * @param PaymentCustomerId|null $paymentCustomerId
     * @param ConnectedAccountId|null $connectedAccountId
     * @param BillingAddress|null $billingAddress
     * @param BillingContact|null $billingContact
     * @param BillingMethod|null $billingMethod
     * @param TaxInfo|null $taxInfo
     */
    public function __construct(
        private readonly MonetizationAccountIdentifier $monetizationAccountIdentifier,
        private readonly AccountIdentifier             $accountIdentifier,
        private array                                  $capabilities,
        private ?PaymentCustomerId                     $paymentCustomerId,
        private ?ConnectedAccountId                    $connectedAccountId,
        private ?BillingAddress                        $billingAddress = null,
        private ?BillingContact                        $billingContact = null,
        private ?BillingMethod                         $billingMethod = null,
        private ?TaxInfo                               $taxInfo = null,
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

    public function stripeCustomerId(): ?PaymentCustomerId
    {
        return $this->paymentCustomerId;
    }

    public function stripeConnectedAccountId(): ?ConnectedAccountId
    {
        return $this->connectedAccountId;
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
     * @throws PaymentCustomerAlreadyLinkedException
     */
    public function linkStripeCustomer(PaymentCustomerId $stripeCustomerId): void
    {
        if ($this->paymentCustomerId !== null) {
            throw new PaymentCustomerAlreadyLinkedException();
        }

        $this->paymentCustomerId = $stripeCustomerId;
    }

    /**
     * @throws ConnectedAccountAlreadyLinkedException
     */
    public function linkStripeConnectedAccount(ConnectedAccountId $stripeConnectedAccountId): void
    {
        if ($this->connectedAccountId !== null) {
            throw new ConnectedAccountAlreadyLinkedException();
        }

        $this->connectedAccountId = $stripeConnectedAccountId;
    }

    /**
     * 購入操作を実行可能か検証
     *
     * @throws CapabilityNotGrantedException
     * @throws PaymentCustomerNotLinkedException
     */
    public function assertCanMakePurchase(): void
    {
        if (! $this->canPurchase()) {
            throw new CapabilityNotGrantedException(Capability::PURCHASE);
        }

        if ($this->paymentCustomerId === null) {
            throw new PaymentCustomerNotLinkedException();
        }
    }

    /**
     * 販売操作を実行可能か検証
     *
     * @throws CapabilityNotGrantedException
     * @throws ConnectedAccountNotLinkedException
     */
    public function assertCanSell(): void
    {
        if (! $this->canSell()) {
            throw new CapabilityNotGrantedException(Capability::SELL);
        }

        if ($this->connectedAccountId === null) {
            throw new ConnectedAccountNotLinkedException();
        }
    }

    /**
     * 出金受取が可能か検証
     *
     * @throws CapabilityNotGrantedException
     * @throws ConnectedAccountNotLinkedException
     */
    public function assertCanReceivePayout(): void
    {
        if (! $this->canReceivePayout()) {
            throw new CapabilityNotGrantedException(Capability::RECEIVE_PAYOUT);
        }

        if ($this->connectedAccountId === null) {
            throw new ConnectedAccountNotLinkedException();
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
