<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Application\UseCase\Command\CreateInvoice;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Domain\ValueObject\CountryCode;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceInput;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceInterface;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\Factory\InvoiceFactoryInterface;
use Source\Monetization\Billing\Domain\Repository\InvoiceRepositoryInterface;
use Source\Monetization\Billing\Domain\Service\TaxDocumentPolicyServiceInterface;
use Source\Monetization\Billing\Domain\ValueObject\Discount;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocument;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocumentType;
use Source\Monetization\Billing\Domain\ValueObject\TaxLine;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateInvoiceTest extends TestCase
{
    /**
     * 正常系: 商品明細と送料からInvoiceが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessCreatesInvoiceWithProductAndShipping(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUlid());
        $customerIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $lines = [
            new InvoiceLine(
                'Test Product',
                new Money(1000, Currency::JPY),
                2,
            ),
        ];
        $shippingCost = new Money(500, Currency::JPY);
        $currency = Currency::JPY;
        $discount = new Discount(new Percentage(10), 'DISCOUNT_CODE');
        $taxLines = [new TaxLine('VAT', new Percentage(10), false)];
        $sellerCountry = CountryCode::JAPAN;
        $sellerRegistered = true;
        $qualifiedInvoiceRequired = true;
        $buyerCountry = CountryCode::UNITED_STATES;
        $buyerIsBusiness = false;
        $paidByCard = true;
        $registrationNumber = 'T-12345';

        $input = new CreateInvoiceInput(
            orderIdentifier: $orderIdentifier,
            customerIdentifier: $customerIdentifier,
            lines: $lines,
            shippingCost: $shippingCost,
            currency: $currency,
            discount: $discount,
            taxLines: $taxLines,
            sellerCountry: $sellerCountry,
            sellerRegistered: $sellerRegistered,
            qualifiedInvoiceRequired: $qualifiedInvoiceRequired,
            buyerCountry: $buyerCountry,
            buyerIsBusiness: $buyerIsBusiness,
            paidByCard: $paidByCard,
            registrationNumber: $registrationNumber,
        );

        $expectedInvoice = $this->createDummyInvoice($customerIdentifier, $currency);

        $taxDocument = new TaxDocument(
            TaxDocumentType::JP_QUALIFIED_INVOICE,
            $sellerCountry,
            $registrationNumber,
            new DateTimeImmutable(),
            null,
        );

        $invoiceFactory = Mockery::mock(InvoiceFactoryInterface::class);
        $invoiceFactory->shouldReceive('create')
            ->once()
            ->withArgs(function (
                OrderIdentifier $orderId,
                UserIdentifier $customerId,
                array $invoiceLines,
                Currency $cur,
                DateTimeImmutable $issuedAt,
                DateTimeImmutable $dueDate,
                ?Discount $disc,
                array $taxes,
            ) use ($orderIdentifier, $customerIdentifier, $currency, $discount, $taxLines) {
                // 2つのInvoiceLine: 商品明細 + 送料
                if (count($invoiceLines) !== 2) {
                    return false;
                }

                // 商品明細の検証
                $productLine = $invoiceLines[0];
                if ($productLine->description() !== 'Test Product' ||
                    $productLine->unitPrice()->amount() !== 1000 ||
                    $productLine->quantity() !== 2) {
                    return false;
                }

                // 送料明細の検証
                $shippingLine = $invoiceLines[1];
                if ($shippingLine->description() !== 'Shipping' ||
                    $shippingLine->unitPrice()->amount() !== 500 ||
                    $shippingLine->quantity() !== 1) {
                    return false;
                }

                return $orderId === $orderIdentifier &&
                    $customerId === $customerIdentifier &&
                    $cur === $currency &&
                    $disc === $discount &&
                    $taxes === $taxLines;
            })
            ->andReturn($expectedInvoice);

        $taxDocumentPolicyService = Mockery::mock(TaxDocumentPolicyServiceInterface::class);
        $taxDocumentPolicyService->shouldReceive('decide')
            ->once()
            ->withArgs(function (
                CountryCode $sellerC,
                bool $sellerReg,
                bool $qualifiedReq,
                CountryCode $buyerC,
                bool $buyerBiz,
                bool $paidCard,
                ?string $regNum,
            ) use ($sellerCountry, $buyerCountry, $registrationNumber) {
                return $sellerC === $sellerCountry &&
                    $sellerReg === true &&
                    $qualifiedReq === true &&
                    $buyerC === $buyerCountry &&
                    $buyerBiz === false &&
                    $paidCard === true &&
                    $regNum === $registrationNumber;
            })
            ->andReturn($taxDocument);

        $invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $invoiceRepository->shouldReceive('save')
            ->once()
            ->with($expectedInvoice);

        $this->app->instance(InvoiceFactoryInterface::class, $invoiceFactory);
        $this->app->instance(TaxDocumentPolicyServiceInterface::class, $taxDocumentPolicyService);
        $this->app->instance(InvoiceRepositoryInterface::class, $invoiceRepository);
        $useCase = $this->app->make(CreateInvoiceInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($expectedInvoice, $result);
    }

    /**
     * 正常系: 送料が0円の場合、送料明細は追加されないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWithZeroShippingCost(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUlid());
        $customerIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $lines = [
            new InvoiceLine(
                'Test Product',
                new Money(1000, Currency::JPY),
                1,
            ),
        ];
        $shippingCost = new Money(0, Currency::JPY);

        $input = new CreateInvoiceInput(
            orderIdentifier: $orderIdentifier,
            customerIdentifier: $customerIdentifier,
            lines: $lines,
            shippingCost: $shippingCost,
            currency: Currency::JPY,
            discount: null,
            taxLines: [],
            sellerCountry: CountryCode::JAPAN,
            sellerRegistered: false,
            qualifiedInvoiceRequired: false,
            buyerCountry: CountryCode::JAPAN,
            buyerIsBusiness: false,
            paidByCard: false,
            registrationNumber: null,
        );

        $expectedInvoice = $this->createDummyInvoice($customerIdentifier, Currency::JPY);

        $invoiceFactory = Mockery::mock(InvoiceFactoryInterface::class);
        $invoiceFactory->shouldReceive('create')
            ->once()
            ->withArgs(function (
                OrderIdentifier $orderId,
                UserIdentifier $customerId,
                array $invoiceLines,
            ) {
                // 送料0円の場合、商品明細のみ（1件）
                return count($invoiceLines) === 1 &&
                    $invoiceLines[0]->description() === 'Test Product';
            })
            ->andReturn($expectedInvoice);

        $taxDocumentPolicyService = Mockery::mock(TaxDocumentPolicyServiceInterface::class);
        $taxDocumentPolicyService->shouldReceive('decide')
            ->once()
            ->andReturn(new TaxDocument(
                TaxDocumentType::SIMPLE_RECEIPT,
                CountryCode::JAPAN,
                null,
                new DateTimeImmutable(),
                null,
            ));

        $invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $invoiceRepository->shouldReceive('save')
            ->once()
            ->with($expectedInvoice);

        $this->app->instance(InvoiceFactoryInterface::class, $invoiceFactory);
        $this->app->instance(TaxDocumentPolicyServiceInterface::class, $taxDocumentPolicyService);
        $this->app->instance(InvoiceRepositoryInterface::class, $invoiceRepository);
        $useCase = $this->app->make(CreateInvoiceInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($expectedInvoice, $result);
    }

    /**
     * 正常系: 複数商品明細がある場合、全て含まれること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWithMultipleLines(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUlid());
        $customerIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $lines = [
            new InvoiceLine(
                'Product A',
                new Money(1000, Currency::JPY),
                2,
            ),
            new InvoiceLine(
                'Product B',
                new Money(500, Currency::JPY),
                3,
            ),
        ];
        $shippingCost = new Money(300, Currency::JPY);

        $input = new CreateInvoiceInput(
            orderIdentifier: $orderIdentifier,
            customerIdentifier: $customerIdentifier,
            lines: $lines,
            shippingCost: $shippingCost,
            currency: Currency::JPY,
            discount: null,
            taxLines: [],
            sellerCountry: CountryCode::JAPAN,
            sellerRegistered: false,
            qualifiedInvoiceRequired: false,
            buyerCountry: CountryCode::JAPAN,
            buyerIsBusiness: false,
            paidByCard: false,
            registrationNumber: null,
        );

        $expectedInvoice = $this->createDummyInvoice($customerIdentifier, Currency::JPY);

        $invoiceFactory = Mockery::mock(InvoiceFactoryInterface::class);
        $invoiceFactory->shouldReceive('create')
            ->once()
            ->withArgs(function (
                OrderIdentifier $orderId,
                UserIdentifier $customerId,
                array $invoiceLines,
            ) {
                // 2つの商品明細 + 送料 = 3件
                return count($invoiceLines) === 3 &&
                    $invoiceLines[0]->description() === 'Product A' &&
                    $invoiceLines[1]->description() === 'Product B' &&
                    $invoiceLines[2]->description() === 'Shipping';
            })
            ->andReturn($expectedInvoice);

        $taxDocumentPolicyService = Mockery::mock(TaxDocumentPolicyServiceInterface::class);
        $taxDocumentPolicyService->shouldReceive('decide')
            ->once()
            ->andReturn(new TaxDocument(
                TaxDocumentType::SIMPLE_RECEIPT,
                CountryCode::JAPAN,
                null,
                new DateTimeImmutable(),
                null,
            ));

        $invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $invoiceRepository->shouldReceive('save')
            ->once()
            ->with($expectedInvoice);

        $this->app->instance(InvoiceFactoryInterface::class, $invoiceFactory);
        $this->app->instance(TaxDocumentPolicyServiceInterface::class, $taxDocumentPolicyService);
        $this->app->instance(InvoiceRepositoryInterface::class, $invoiceRepository);
        $useCase = $this->app->make(CreateInvoiceInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($expectedInvoice, $result);
    }

    /**
     * 異常系: 商品明細が空の場合は例外となること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessRejectsEmptyLines(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUlid());
        $customerIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $shippingCost = new Money(500, Currency::JPY);

        $input = new CreateInvoiceInput(
            orderIdentifier: $orderIdentifier,
            customerIdentifier: $customerIdentifier,
            lines: [],
            shippingCost: $shippingCost,
            currency: Currency::JPY,
            discount: null,
            taxLines: [],
            sellerCountry: CountryCode::JAPAN,
            sellerRegistered: false,
            qualifiedInvoiceRequired: false,
            buyerCountry: CountryCode::JAPAN,
            buyerIsBusiness: false,
            paidByCard: false,
            registrationNumber: null,
        );

        $invoiceFactory = Mockery::mock(InvoiceFactoryInterface::class);
        $invoiceFactory->shouldNotReceive('create');

        $taxDocumentPolicyService = Mockery::mock(TaxDocumentPolicyServiceInterface::class);
        $taxDocumentPolicyService->shouldNotReceive('decide');

        $invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $invoiceRepository->shouldNotReceive('save');

        $this->app->instance(InvoiceFactoryInterface::class, $invoiceFactory);
        $this->app->instance(TaxDocumentPolicyServiceInterface::class, $taxDocumentPolicyService);
        $this->app->instance(InvoiceRepositoryInterface::class, $invoiceRepository);
        $useCase = $this->app->make(CreateInvoiceInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('At least one product line is required.');

        $useCase->process($input);
    }

    private function createDummyInvoice(UserIdentifier $customerIdentifier, Currency $currency): Invoice
    {
        $issuedAt = new DateTimeImmutable();
        $dueDate = $issuedAt->modify('+14 days');

        return new Invoice(
            new InvoiceIdentifier(StrTestHelper::generateUlid()),
            new OrderIdentifier(StrTestHelper::generateUlid()),
            $customerIdentifier,
            [new InvoiceLine('Test', new Money(1000, $currency), 1)],
            new Money(1000, $currency),
            new Money(0, $currency),
            new Money(0, $currency),
            new Money(1000, $currency),
            $issuedAt,
            $dueDate,
            InvoiceStatus::ISSUED,
        );
    }
}
