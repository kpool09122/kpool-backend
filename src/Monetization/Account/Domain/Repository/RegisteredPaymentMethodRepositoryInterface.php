<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Repository;

use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\RegisteredPaymentMethodIdentifier;

interface RegisteredPaymentMethodRepositoryInterface
{
    public function findById(RegisteredPaymentMethodIdentifier $identifier): ?RegisteredPaymentMethod;

    public function findByPaymentMethodId(PaymentMethodId $paymentMethodId): ?RegisteredPaymentMethod;

    public function findDefaultByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): ?RegisteredPaymentMethod;

    /**
     * @return RegisteredPaymentMethod[]
     */
    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): array;

    public function save(RegisteredPaymentMethod $paymentMethod): void;
}
