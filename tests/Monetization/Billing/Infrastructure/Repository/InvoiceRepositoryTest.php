<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\Repository\InvoiceRepositoryInterface;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocument;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocumentType;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\CreateInvoice;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class InvoiceRepositoryTest extends TestCase
{
    /**
     * 正常系: 正しくIDに紐づく請求書を取得できること.
     *
     * @throws BindingResolutionException
     * @return void
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $invoiceIdentifier = new InvoiceIdentifier(StrTestHelper::generateUuid());
        CreateInvoice::create($invoiceIdentifier, [
            'subtotal' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 100,
            'total' => 1100,
            'status' => InvoiceStatus::ISSUED,
        ]);

        $repository = $this->app->make(InvoiceRepositoryInterface::class);
        $result = $repository->findById($invoiceIdentifier);

        $this->assertNotNull($result);
        $this->assertSame((string) $invoiceIdentifier, (string) $result->invoiceIdentifier());
        $this->assertSame(1000, $result->subtotal()->amount());
        $this->assertSame(0, $result->discountAmount()->amount());
        $this->assertSame(100, $result->taxAmount()->amount());
        $this->assertSame(1100, $result->total()->amount());
        $this->assertSame(InvoiceStatus::ISSUED, $result->status());
        $this->assertCount(1, $result->lines());
    }

    /**
     * 正常系: 指定したIDを持つ請求書が存在しない場合、NULLが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(InvoiceRepositoryInterface::class);
        $result = $repository->findById(new InvoiceIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しく新規の請求書を保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewInvoice(): void
    {
        $invoiceId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $customerId = StrTestHelper::generateUuid();
        $issuedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $dueDate = new DateTimeImmutable('2024-01-31 23:59:59');

        $lines = [
            new InvoiceLine('商品A', new Money(500, Currency::JPY), 2),
        ];

        $invoice = new Invoice(
            new InvoiceIdentifier($invoiceId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($customerId),
            $lines,
            new Money(1000, Currency::JPY),
            new Money(0, Currency::JPY),
            new Money(100, Currency::JPY),
            new Money(1100, Currency::JPY),
            $issuedAt,
            $dueDate,
            InvoiceStatus::ISSUED,
        );

        $repository = $this->app->make(InvoiceRepositoryInterface::class);
        $repository->save($invoice);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'order_id' => $orderId,
            'buyer_monetization_account_id' => $customerId,
            'currency' => 'JPY',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 100,
            'total' => 1100,
            'status' => 'issued',
        ]);

        $this->assertDatabaseHas('invoice_lines', [
            'invoice_id' => $invoiceId,
            'description' => '商品A',
            'currency' => 'JPY',
            'unit_price' => 500,
            'quantity' => 2,
        ]);
    }

    /**
     * 正常系: 正しく既存の請求書を更新できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingInvoice(): void
    {
        $invoiceIdentifier = new InvoiceIdentifier(StrTestHelper::generateUuid());
        $orderId = StrTestHelper::generateUuid();
        $customerId = StrTestHelper::generateUuid();

        CreateInvoice::create($invoiceIdentifier, [
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'status' => InvoiceStatus::ISSUED,
        ]);

        $issuedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $dueDate = new DateTimeImmutable('2024-01-31 23:59:59');
        $paidAt = new DateTimeImmutable('2024-01-15 12:00:00');

        $lines = [
            new InvoiceLine('更新後の商品', new Money(2000, Currency::JPY), 1),
        ];

        $invoice = new Invoice(
            $invoiceIdentifier,
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($customerId),
            $lines,
            new Money(2000, Currency::JPY),
            new Money(0, Currency::JPY),
            new Money(200, Currency::JPY),
            new Money(2200, Currency::JPY),
            $issuedAt,
            $dueDate,
            InvoiceStatus::PAID,
            null,
            $paidAt,
        );

        $repository = $this->app->make(InvoiceRepositoryInterface::class);
        $repository->save($invoice);

        $this->assertDatabaseHas('invoices', [
            'id' => (string) $invoiceIdentifier,
            'subtotal' => 2000,
            'total' => 2200,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('invoice_lines', [
            'invoice_id' => (string) $invoiceIdentifier,
            'description' => '更新後の商品',
            'unit_price' => 2000,
            'quantity' => 1,
        ]);
    }

    /**
     * 正常系: 税書類情報を含む請求書を正しく保存・取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindWithTaxDocument(): void
    {
        $invoiceId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $customerId = StrTestHelper::generateUuid();
        $issuedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $dueDate = new DateTimeImmutable('2024-01-31 23:59:59');
        $taxDocumentDeadline = new DateTimeImmutable('2024-02-15 23:59:59');

        $lines = [
            new InvoiceLine('商品', new Money(1000, Currency::JPY), 1),
        ];

        $taxDocument = new TaxDocument(
            TaxDocumentType::JP_QUALIFIED_INVOICE,
            CountryCode::JAPAN,
            'T1234567890123',
            $taxDocumentDeadline,
        );

        $invoice = new Invoice(
            new InvoiceIdentifier($invoiceId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($customerId),
            $lines,
            new Money(1000, Currency::JPY),
            new Money(0, Currency::JPY),
            new Money(100, Currency::JPY),
            new Money(1100, Currency::JPY),
            $issuedAt,
            $dueDate,
            InvoiceStatus::ISSUED,
            $taxDocument,
        );

        $repository = $this->app->make(InvoiceRepositoryInterface::class);
        $repository->save($invoice);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'tax_document_type' => 'jp_qualified_invoice',
            'tax_document_country' => 'JP',
            'tax_document_registration_number' => 'T1234567890123',
        ]);

        $result = $repository->findById(new InvoiceIdentifier($invoiceId));

        $this->assertNotNull($result);
        $this->assertNotNull($result->taxDocument());
        $this->assertSame(TaxDocumentType::JP_QUALIFIED_INVOICE, $result->taxDocument()->type());
        $this->assertSame(CountryCode::JAPAN, $result->taxDocument()->country());
        $this->assertSame('T1234567890123', $result->taxDocument()->registrationNumber());
    }

    /**
     * 正常系: 複数の明細行を持つ請求書を正しく保存・取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindWithMultipleLines(): void
    {
        $invoiceId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $customerId = StrTestHelper::generateUuid();
        $issuedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $dueDate = new DateTimeImmutable('2024-01-31 23:59:59');

        $lines = [
            new InvoiceLine('商品A', new Money(500, Currency::JPY), 2),
            new InvoiceLine('商品B', new Money(1500, Currency::JPY), 1),
            new InvoiceLine('商品C', new Money(250, Currency::JPY), 4),
        ];

        $invoice = new Invoice(
            new InvoiceIdentifier($invoiceId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($customerId),
            $lines,
            new Money(3500, Currency::JPY),
            new Money(0, Currency::JPY),
            new Money(350, Currency::JPY),
            new Money(3850, Currency::JPY),
            $issuedAt,
            $dueDate,
            InvoiceStatus::ISSUED,
        );

        $repository = $this->app->make(InvoiceRepositoryInterface::class);
        $repository->save($invoice);

        $result = $repository->findById(new InvoiceIdentifier($invoiceId));

        $this->assertNotNull($result);
        $this->assertCount(3, $result->lines());
    }

    /**
     * 正常系: 失効した請求書を正しく保存・取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindVoidedInvoice(): void
    {
        $invoiceId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $customerId = StrTestHelper::generateUuid();
        $issuedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $dueDate = new DateTimeImmutable('2024-01-31 23:59:59');
        $voidedAt = new DateTimeImmutable('2024-01-10 15:00:00');

        $lines = [
            new InvoiceLine('商品', new Money(1000, Currency::JPY), 1),
        ];

        $invoice = new Invoice(
            new InvoiceIdentifier($invoiceId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($customerId),
            $lines,
            new Money(1000, Currency::JPY),
            new Money(0, Currency::JPY),
            new Money(100, Currency::JPY),
            new Money(1100, Currency::JPY),
            $issuedAt,
            $dueDate,
            InvoiceStatus::VOID,
            null,
            null,
            $voidedAt,
            'キャンセルのため',
        );

        $repository = $this->app->make(InvoiceRepositoryInterface::class);
        $repository->save($invoice);

        $result = $repository->findById(new InvoiceIdentifier($invoiceId));

        $this->assertNotNull($result);
        $this->assertSame(InvoiceStatus::VOID, $result->status());
        $this->assertNotNull($result->voidedAt());
        $this->assertSame('キャンセルのため', $result->voidReason());
    }
}
