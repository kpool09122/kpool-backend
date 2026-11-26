<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Repository;

use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;

interface PaymentMethodRepositoryInterface
{
    public function findById(PaymentMethodIdentifier $identifier): ?PaymentMethod;

    public function findByExternalPaymentMethodId(ExternalPaymentMethodId $externalPaymentMethodId, MonetizationAccountIdentifier $monetizationAccountIdentifier): ?PaymentMethod;

    public function findDefaultByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): ?PaymentMethod;

    /**
     * @return PaymentMethod[]
     */
    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): array;

    public function save(PaymentMethod $paymentMethod): void;
}
