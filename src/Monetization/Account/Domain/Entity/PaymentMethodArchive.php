<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Entity;

use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodArchiveIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

readonly class PaymentMethodArchive
{
    public function __construct(
        public PaymentMethodArchiveIdentifier $identifier,
        public PaymentMethodIdentifier $paymentMethodIdentifier,
        public MonetizationAccountIdentifier $monetizationAccountIdentifier,
        public ExternalPaymentMethodId $externalPaymentMethodId,
        public PaymentMethodType $type,
        public ?string $brand,
        public ?string $last4,
        public DateTimeImmutable $archivedAt,
    ) {
    }
}
