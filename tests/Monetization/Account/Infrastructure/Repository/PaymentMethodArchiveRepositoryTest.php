<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Repository;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\Entity\PaymentMethodArchive;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodArchiveIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Infrastructure\Repository\PaymentMethodArchiveRepository;
use Source\Monetization\Account\Infrastructure\Repository\PaymentMethodRepository;
use Tests\Helper\CreatePaymentMethod;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PaymentMethodArchiveRepositoryTest extends TestCase
{
    #[Group('useDb')]
    public function testArchivesBeforeDeletingCurrentAndRetainsOriginalReference(): void
    {
        $id = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        CreatePaymentMethod::create((string) $id, ['brand' => 'visa','last4' => '4242','exp_month' => 12,'exp_year' => 2030,'is_default' => true]);
        $repository = new PaymentMethodRepository();
        $method = $repository->findById($id);
        self::assertNotNull($method);
        $archive = new PaymentMethodArchive(new PaymentMethodArchiveIdentifier(StrTestHelper::generateUuid()), $method->paymentMethodIdentifier(), $method->monetizationAccountIdentifier(), $method->externalPaymentMethodId(), $method->type(), $method->meta()?->brand(), $method->meta()?->last4(), new DateTimeImmutable('2026-11-26T09:00:00+00:00'));
        $archives = new PaymentMethodArchiveRepository();
        $archives->save($archive);
        $repository->delete($method->paymentMethodIdentifier());
        self::assertNull($repository->findById($id));
        $restored = $archives->findByPaymentMethodIdentifier($id);
        self::assertNotNull($restored);
        self::assertSame((string) $id, (string) $restored->paymentMethodIdentifier);
        self::assertSame('4242', $restored->last4);
        self::assertSame('visa', $restored->brand);
        self::assertSame($archive->archivedAt->format(DATE_ATOM), $restored->archivedAt->format(DATE_ATOM));
    }
}
