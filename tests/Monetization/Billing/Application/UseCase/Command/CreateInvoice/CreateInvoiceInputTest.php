<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Application\UseCase\Command\CreateInvoice;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\CountryCode;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceInput;
use Source\Monetization\Billing\Domain\ValueObject\Discount;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\TaxLine;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;

class CreateInvoiceInputTest extends TestCase
{
    /**
     * 正常系: 全てのゲッターが正しい値を返すこと.
     *
     * @return void
     */
    public function test__construct(): void
    {
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

        $this->assertSame($customerIdentifier, $input->customerIdentifier());
        $this->assertSame($lines, $input->lines());
        $this->assertSame($shippingCost, $input->shippingCost());
        $this->assertSame($currency, $input->currency());
        $this->assertSame($discount, $input->discount());
        $this->assertSame($taxLines, $input->taxLines());
        $this->assertSame($sellerCountry, $input->sellerCountry());
        $this->assertTrue($input->sellerRegistered());
        $this->assertTrue($input->qualifiedInvoiceRequired());
        $this->assertSame($buyerCountry, $input->buyerCountry());
        $this->assertFalse($input->buyerIsBusiness());
        $this->assertTrue($input->paidByCard());
        $this->assertSame($registrationNumber, $input->registrationNumber());
    }

    /**
     * 正常系: オプションパラメータがnullの場合でも正しく動作すること.
     *
     * @return void
     */
    public function testWithNullOptionalParameters(): void
    {
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

        $this->assertNull($input->discount());
        $this->assertNull($input->registrationNumber());
        $this->assertSame([], $input->taxLines());
    }
}
