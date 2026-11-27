<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Entity\PaymentMethodArchive;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodArchiveIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Tests\Helper\StrTestHelper;

class PaymentMethodArchiveTest extends TestCase
{
    public function testPreservesDeletionSnapshot(): void
    {
        $method = new PaymentMethod(new PaymentMethodIdentifier(StrTestHelper::generateUuid()), new MonetizationAccountIdentifier(StrTestHelper::generateUuid()), new ExternalPaymentMethodId('pm_test12345'), PaymentMethodType::CARD, new PaymentMethodMeta('visa', '4242', 12, 2030), true, PaymentMethodStatus::INACTIVE);
        $archivedAt = new DateTimeImmutable('2026-11-26T09:00:00Z');
        $archive = new PaymentMethodArchive(new PaymentMethodArchiveIdentifier(StrTestHelper::generateUuid()), $method->paymentMethodIdentifier(), $method->monetizationAccountIdentifier(), $method->externalPaymentMethodId(), $method->type(), $method->meta()?->brand(), $method->meta()?->last4(), $archivedAt);
        self::assertSame($method->paymentMethodIdentifier(), $archive->paymentMethodIdentifier);
        self::assertSame($method->monetizationAccountIdentifier(), $archive->monetizationAccountIdentifier);
        self::assertSame($method->externalPaymentMethodId(), $archive->externalPaymentMethodId);
        self::assertSame($method->meta()?->brand(), $archive->brand);
        self::assertSame($method->meta()?->last4(), $archive->last4);
        self::assertSame($method->type(), $archive->type);
        self::assertSame($archivedAt, $archive->archivedAt);
    }
}
