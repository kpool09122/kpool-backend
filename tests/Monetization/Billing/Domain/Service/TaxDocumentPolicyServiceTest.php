<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Billing\Domain\Service\TaxDocumentPolicyServiceInterface;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocumentType;
use Source\Shared\Domain\ValueObject\CountryCode;
use Tests\TestCase;

class TaxDocumentPolicyServiceTest extends TestCase
{
    /**
     * 正常系: 日本の登録済み事業者は適格請求書を発行することを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testJapanRegisteredIssuesQualifiedInvoice(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);
        $deadline = new DateTimeImmutable('+1 day');

        $document = $policy->decide(
            CountryCode::JAPAN,
            true,
            true,
            CountryCode::UNITED_STATES,
            true,
            true,
            'T-12345',
            $deadline,
        );

        $this->assertSame(TaxDocumentType::JP_QUALIFIED_INVOICE, $document->type());
        $this->assertSame('T-12345', $document->registrationNumber());
        $this->assertSame($deadline, $document->issueDeadline());
    }

    /**
     * 正常系: 韓国登録済みの国内取引は電子税計算書を選択することを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testKoreaRegisteredIssuesElectronicTaxInvoice(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);

        $document = $policy->decide(
            CountryCode::KOREA_REPUBLIC,
            true,
            true,
            CountryCode::KOREA_REPUBLIC,
            true,
            true,
            '123-45-67890',
            new DateTimeImmutable('+1 day')
        );

        $this->assertSame(TaxDocumentType::KR_ELECTRONIC_TAX_INVOICE, $document->type());
    }

    /**
     * 正常系: 韓国未登録かつ買い手が事業者の場合は逆課税通知となることを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testKoreaUnregisteredBusinessTriggersReverseCharge(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);

        $document = $policy->decide(
            CountryCode::KOREA_REPUBLIC,
            false,
            true,
            CountryCode::KOREA_REPUBLIC,
            true,
            false,
            null,
            new DateTimeImmutable('+1 day'),
            'Seller not VAT registered'
        );

        $this->assertSame(TaxDocumentType::REVERSE_CHARGE_NOTICE, $document->type());
        $this->assertSame('Seller not VAT registered', $document->reason());
    }

    /**
     * 正常系: 韓国未登録かつ買い手が個人でカード決済ならカードレシートを選択することを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testKoreaUnregisteredIndividualCardIssuesCardReceipt(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);

        $document = $policy->decide(
            CountryCode::KOREA_REPUBLIC,
            false,
            true,
            CountryCode::KOREA_REPUBLIC,
            false,
            true,
            null,
            new DateTimeImmutable('+1 day')
        );

        $this->assertSame(TaxDocumentType::CARD_RECEIPT, $document->type());
    }

    /**
     * 正常系: 韓国未登録かつ買い手が個人でカード以外決済なら現金レシートを選択することを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testKoreaUnregisteredIndividualCashIssuesCashReceipt(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);

        $document = $policy->decide(
            CountryCode::KOREA_REPUBLIC,
            false,
            true,
            CountryCode::KOREA_REPUBLIC,
            false,
            false,
            null,
            new DateTimeImmutable('+1 day')
        );

        $this->assertSame(TaxDocumentType::CASH_RECEIPT, $document->type());
    }

    /**
     * 正常系: 上記以外のケースでは簡易領収書を返すことを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testDefaultReturnsSimpleReceipt(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);

        $document = $policy->decide(
            CountryCode::UNITED_STATES,
            true,
            true,
            CountryCode::JAPAN,
            false,
            true,
            null,
            new DateTimeImmutable('+1 day')
        );

        $this->assertSame(TaxDocumentType::SIMPLE_RECEIPT, $document->type());
    }

    /**
     * 正常系: 日本で未登録の場合は簡易領収書にフォールバックすることを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testJapanUnregisteredReturnsSimpleReceipt(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);

        $document = $policy->decide(
            CountryCode::JAPAN,
            false,
            true,
            CountryCode::JAPAN,
            true,
            true,
            null,
            new DateTimeImmutable('+1 day')
        );

        $this->assertSame(TaxDocumentType::SIMPLE_RECEIPT, $document->type());
    }

    /**
     * 正常系: 適格不要フラグがfalseの場合は簡易領収書を返すことを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testQualifiedInvoiceOptionalFlagFallsBackToSimpleReceipt(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);

        $document = $policy->decide(
            CountryCode::JAPAN,
            true,
            false,
            CountryCode::JAPAN,
            true,
            true,
            null,
            new DateTimeImmutable('+1 day')
        );

        $this->assertSame(TaxDocumentType::SIMPLE_RECEIPT, $document->type());
    }

    /**
     * 異常系: 日本で適格請求書が必要なのに登録番号が無い場合は例外となることを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testJapanRequiresRegistrationNumberWhenQualified(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);
        $this->expectException(\InvalidArgumentException::class);

        $policy->decide(
            CountryCode::JAPAN,
            true,
            true,
            CountryCode::JAPAN,
            true,
            true,
            null,
            new DateTimeImmutable('+1 day')
        );
    }

    /**
     * 異常系: 韓国の国内取引で登録済みなのに登録番号がない場合は例外となることを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testKoreaRegisteredDomesticRequiresRegistrationNumber(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);
        $this->expectException(\InvalidArgumentException::class);

        $policy->decide(
            CountryCode::KOREA_REPUBLIC,
            true,
            true,
            CountryCode::KOREA_REPUBLIC,
            true,
            true,
            null,
            new DateTimeImmutable('+1 day')
        );
    }

    /**
     * 正常系: レシート系（簡易領収書）では期限がnullでも生成されることを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSimpleReceiptAllowsNullDeadline(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);

        $document = $policy->decide(
            CountryCode::UNITED_STATES,
            true,
            true,
            CountryCode::UNITED_STATES,
            false,
            true,
            null,
            null
        );

        $this->assertSame(TaxDocumentType::SIMPLE_RECEIPT, $document->type());
        $this->assertNotNull($document->issueDeadline());
    }

    /**
     * 異常系: 日本の適格請求書で期限がnullの場合は例外となることを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testJapanQualifiedInvoiceRequiresDeadline(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);
        $this->expectException(\InvalidArgumentException::class);

        $policy->decide(
            CountryCode::JAPAN,
            true,
            true,
            CountryCode::UNITED_STATES,
            true,
            true,
            'T-12345',
            null
        );
    }

    /**
     * 異常系: 韓国国内登録済み取引で期限がnullの場合は例外となることを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testKoreaRegisteredDomesticRequiresDeadline(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);
        $this->expectException(\InvalidArgumentException::class);

        $policy->decide(
            CountryCode::KOREA_REPUBLIC,
            true,
            true,
            CountryCode::KOREA_REPUBLIC,
            true,
            true,
            '123-45-67890',
            null
        );
    }

    /**
     * 異常系: 逆課税通知で期限がnullの場合は例外となることを検証.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testReverseChargeRequiresDeadline(): void
    {
        $policy = $this->app->make(TaxDocumentPolicyServiceInterface::class);
        $this->expectException(\InvalidArgumentException::class);

        $policy->decide(
            CountryCode::KOREA_REPUBLIC,
            false,
            true,
            CountryCode::KOREA_REPUBLIC,
            true,
            false,
            null,
            null,
            'Seller not VAT registered'
        );
    }
}
