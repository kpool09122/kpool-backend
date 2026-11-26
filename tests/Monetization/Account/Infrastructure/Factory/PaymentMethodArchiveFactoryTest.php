<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Factory;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Infrastructure\Factory\PaymentMethodArchiveFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Tests\Helper\PaymentCardFixture;
use Tests\Helper\StrTestHelper;

class PaymentMethodArchiveFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGeneratesArchiveIdentityAndPreservesCardDisplayInformation(): void
    {
        $method = new PaymentMethod(new PaymentMethodIdentifier(StrTestHelper::generateUuid()), new MonetizationAccountIdentifier(StrTestHelper::generateUuid()), new ExternalPaymentMethodId('pm_test12345'), PaymentMethodType::CARD, new PaymentMethodMeta('visa', '4242', 12, 2030), true, PaymentMethodStatus::INACTIVE);
        $archivedAt = new DateTimeImmutable('2026-11-26T09:00:00Z');
        $archiveId = StrTestHelper::generateUuid();
        $uuidGenerator = PaymentCardFixture::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->once()->andReturn($archiveId);
        $archive = (new PaymentMethodArchiveFactory($uuidGenerator))->create($method, $archivedAt);
        self::assertSame($archiveId, (string) $archive->identifier);
        self::assertNotSame((string) $method->paymentMethodIdentifier(), (string) $archive->identifier);
        self::assertSame($method->paymentMethodIdentifier(), $archive->paymentMethodIdentifier);
        self::assertSame($method->monetizationAccountIdentifier(), $archive->monetizationAccountIdentifier);
        self::assertSame($method->externalPaymentMethodId(), $archive->externalPaymentMethodId);
        self::assertSame($method->meta()?->brand(), $archive->brand);
        self::assertSame($method->meta()?->last4(), $archive->last4);
        self::assertSame($method->type(), $archive->type);
        self::assertSame($archivedAt, $archive->archivedAt);
    }
}
