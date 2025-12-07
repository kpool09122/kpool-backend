<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\Factory;

use DateTimeImmutable;
use DomainException;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Domain\ValueObject\CountryCode;
use Source\Monetization\Billing\Domain\Factory\InvoiceFactoryInterface;
use Source\Monetization\Billing\Domain\Service\TaxDocumentPolicyServiceInterface;
use Source\Monetization\Billing\Domain\ValueObject\Discount;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocument;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocumentType;
use Source\Monetization\Billing\Domain\ValueObject\TaxLine;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class InvoiceFactoryTest extends TestCase
{
    /**
     * 正常系: 正しくInvoiceインスタンスが作成できること.
     *
     * @return void
     * @throws Exception
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $invoiceIdentifier = new InvoiceIdentifier(StrTestHelper::generateUlid());
        $customerIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $issuedAt = new DateTimeImmutable('2024-01-01');
        $dueDate = $issuedAt->modify('+14 days');
        $sellerCountry = CountryCode::JAPAN;
        $sellerRegistered = true;
        $qualifiedInvoiceRequired = true;
        $buyerCountry = CountryCode::UNITED_STATES;
        $buyerIsBusiness = true;
        $paidByCard = true;
        $registrationNumber = 'T-12345';

        $taxDocument = new TaxDocument(
            TaxDocumentType::JP_QUALIFIED_INVOICE,
            $sellerCountry,
            $registrationNumber,
            $dueDate,
            null,
        );

        $generator = Mockery::mock(UlidGeneratorInterface::class);
        $generator->shouldReceive('generate')
            ->once()
            ->andReturn((string)$invoiceIdentifier);

        $service = Mockery::mock(TaxDocumentPolicyServiceInterface::class);
        $service->shouldReceive('decide')
            ->once()
            ->with(
                $sellerCountry,
                $sellerRegistered,
                $qualifiedInvoiceRequired,
                $buyerCountry,
                $buyerIsBusiness,
                $paidByCard,
                $registrationNumber,
                $dueDate,
                null
            )
            ->andReturn($taxDocument);

        $this->app->instance(UlidGeneratorInterface::class, $generator);
        $this->app->instance(TaxDocumentPolicyServiceInterface::class, $service);
        $factory = $this->app->make(InvoiceFactoryInterface::class);

        $invoiceLines = [new InvoiceLine('Pro plan', new Money(500, Currency::JPY), 2)];
        $currency = Currency::JPY;
        $taxLines = [new TaxLine('VAT', new Percentage(10), false)];
        $discount = new Discount(new Percentage(10), 'TEN_OFF');
        $invoice = $factory->create(
            $customerIdentifier,
            $invoiceLines,
            $currency,
            $issuedAt,
            $dueDate,
            $discount,
            $taxLines,
            $sellerCountry,
            $sellerRegistered,
            $qualifiedInvoiceRequired,
            $buyerCountry,
            $buyerIsBusiness,
            $paidByCard,
            $registrationNumber,
        );

        $this->assertSame((string)$invoiceIdentifier, (string)$invoice->invoiceIdentifier());
        $this->assertSame((string)$customerIdentifier, (string)$invoice->customerIdentifier());
        $this->assertSame($invoiceLines, $invoice->lines());
        $this->assertSame(1000, $invoice->subtotal()->amount());
        $this->assertSame($currency, $invoice->subtotal()->currency());
        $this->assertSame(100, $invoice->discountAmount()->amount());
        $this->assertSame($currency, $invoice->discountAmount()->currency());
        $this->assertSame(90, $invoice->taxAmount()->amount());
        $this->assertSame($currency, $invoice->taxAmount()->currency());
        $this->assertSame(990, $invoice->total()->amount());
        $this->assertSame($currency, $invoice->total()->currency());
        $this->assertSame($issuedAt, $invoice->issuedAt());
        $this->assertSame($dueDate, $invoice->dueDate());
        $this->assertSame(InvoiceStatus::ISSUED, $invoice->status());
        $this->assertSame($taxDocument, $invoice->taxDocument());
    }

    /**
     * 異常系: 明細が存在しない場合、例外がスローされること.
     *
     * @throws Exception
     * @return void
     */
    public function testRejectsNoLines(): void
    {
        $issuedAt = new DateTimeImmutable('2024-01-01');
        $dueDate = $issuedAt->modify('+14 days');

        $generator = Mockery::mock(UlidGeneratorInterface::class);
        $generator->shouldNotReceive('generate');

        $service = Mockery::mock(TaxDocumentPolicyServiceInterface::class);
        $service->shouldNotReceive('decide');

        $this->app->instance(UlidGeneratorInterface::class, $generator);
        $this->app->instance(TaxDocumentPolicyServiceInterface::class, $service);
        $factory = $this->app->make(InvoiceFactoryInterface::class);

        $this->expectException(DomainException::class);
        $factory->create(
            new UserIdentifier(StrTestHelper::generateUlid()),
            [],
            Currency::JPY,
            $issuedAt,
            $dueDate,
            new Discount(new Percentage(10), 'TEN_OFF'),
            [new TaxLine('VAT', new Percentage(10), false)],
            CountryCode::JAPAN,
            true,
            true,
            CountryCode::UNITED_STATES,
            true,
            true,
        );
    }

    /**
     * 異常系: 通貨の異なる明細がある場合、例外がスローされること.
     *
     * @throws Exception
     * @return void
     */
    public function testRejectsDifferentCurrencyLines(): void
    {
        $issuedAt = new DateTimeImmutable('2024-01-01');
        $dueDate = $issuedAt->modify('+14 days');

        $generator = Mockery::mock(UlidGeneratorInterface::class);
        $generator->shouldNotReceive('generate');

        $service = Mockery::mock(TaxDocumentPolicyServiceInterface::class);
        $service->shouldNotReceive('decide');

        $this->app->instance(UlidGeneratorInterface::class, $generator);
        $this->app->instance(TaxDocumentPolicyServiceInterface::class, $service);
        $factory = $this->app->make(InvoiceFactoryInterface::class);

        $this->expectException(DomainException::class);
        $factory->create(
            new UserIdentifier(StrTestHelper::generateUlid()),
            [
                new InvoiceLine('Pro plan', new Money(500, Currency::JPY), 2),
                new InvoiceLine('Basic plan', new Money(300, Currency::KRW), 1),
            ],
            Currency::JPY,
            $issuedAt,
            $dueDate,
            new Discount(new Percentage(10), 'TEN_OFF'),
            [new TaxLine('VAT', new Percentage(10), false)],
            CountryCode::JAPAN,
            true,
            true,
            CountryCode::UNITED_STATES,
            true,
            true,
        );
    }
}
