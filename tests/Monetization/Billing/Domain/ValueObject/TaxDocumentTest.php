<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\ValueObject;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocument;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocumentType;
use Source\Shared\Domain\ValueObject\CountryCode;

class TaxDocumentTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成されること.
     *
     * @return void
     * @throws Exception
     */
    public function test__construct(): void
    {
        $type = TaxDocumentType::REVERSE_CHARGE_NOTICE;
        $country = CountryCode::JAPAN;
        $number = 'T1234567890123';
        $deadline = new DateTimeImmutable('now')->modify('+ 3 days');
        $reason = '売り手が課税登録業者でないため';
        $document = new TaxDocument(
            $type,
            $country,
            $number,
            $deadline,
            $reason
        );
        $this->assertSame($type, $document->type());
        $this->assertSame($country, $document->country());
        $this->assertSame($number, $document->registrationNumber());
        $this->assertSame($deadline, $document->issueDeadline());
        $this->assertSame($reason, $document->reason());
    }

    /**
     * 異常系: 課税登録番号を付与する場合、空の値だと例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testWhenNumberIsEmpty(): void
    {
        $type = TaxDocumentType::JP_QUALIFIED_INVOICE;
        $country = CountryCode::JAPAN;
        $number = '';
        $deadline = new DateTimeImmutable('now')->modify('+ 3 days');
        $reason = null;

        $this->expectException(InvalidArgumentException::class);
        new TaxDocument(
            $type,
            $country,
            $number,
            $deadline,
            $reason
        );
    }

    /**
     * 異常系: 課税登録番号を付与する場合、スペースだけだと例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testWhenNumberIsOnlySpace(): void
    {
        $type = TaxDocumentType::JP_QUALIFIED_INVOICE;
        $country = CountryCode::JAPAN;
        $number = '    ';
        $deadline = new DateTimeImmutable('now')->modify('+ 3 days');
        $reason = null;

        $this->expectException(InvalidArgumentException::class);
        new TaxDocument(
            $type,
            $country,
            $number,
            $deadline,
            $reason
        );
    }

    /**
     * 異常系: 理由を付与する場合、空の値だと例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testWhenReasonIsEmpty(): void
    {
        $type = TaxDocumentType::JP_QUALIFIED_INVOICE;
        $country = CountryCode::JAPAN;
        $number = 'T1234567890123';
        $deadline = new DateTimeImmutable('now')->modify('+ 3 days');
        $reason = '';

        $this->expectException(InvalidArgumentException::class);
        new TaxDocument(
            $type,
            $country,
            $number,
            $deadline,
            $reason
        );
    }

    /**
     * 異常系: 理由を付与する場合、スペースだけだと例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testWhenReasonIsOnlySpace(): void
    {
        $type = TaxDocumentType::JP_QUALIFIED_INVOICE;
        $country = CountryCode::JAPAN;
        $number = 'T1234567890123';
        $deadline = new DateTimeImmutable('now')->modify('+ 3 days');
        $reason = '    ';

        $this->expectException(InvalidArgumentException::class);
        new TaxDocument(
            $type,
            $country,
            $number,
            $deadline,
            $reason
        );
    }

    /**
     * 異常系: 逆課税通知の場合、理由がないと例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testRequiredReasonWhenReverseChargeNotice(): void
    {
        $type = TaxDocumentType::REVERSE_CHARGE_NOTICE;
        $country = CountryCode::JAPAN;
        $number = 'T1234567890123';
        $deadline = new DateTimeImmutable('now')->modify('+ 3 days');
        $reason = null;

        $this->expectException(InvalidArgumentException::class);
        new TaxDocument(
            $type,
            $country,
            $number,
            $deadline,
            $reason
        );
    }
}
