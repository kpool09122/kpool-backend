<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;

class ApplicantInfoTest extends TestCase
{
    public function test__construct(): void
    {
        $info = new ApplicantInfo(
            fullName: 'Taro Yamada',
            companyName: 'Company Inc.',
            representativeName: 'John Doe',
        );

        $this->assertSame('Taro Yamada', $info->fullName());
        $this->assertSame('Company Inc.', $info->companyName());
        $this->assertSame('John Doe', $info->representativeName());
    }

    public function testEmptyFullName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ApplicantInfo(
            fullName: '',
        );
    }

    public function testFullNameTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ApplicantInfo(
            fullName: str_repeat('a', 256),
        );
    }

    public function testCompanyNameTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ApplicantInfo(
            fullName: 'Taro Yamada',
            companyName: str_repeat('a', 256),
        );
    }

    public function testRepresentativeNameTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ApplicantInfo(
            fullName: 'Taro Yamada',
            representativeName: str_repeat('a', 256),
        );
    }

    public function testToArray(): void
    {
        $info = new ApplicantInfo(
            fullName: 'Taro Yamada',
            companyName: 'Company Inc.',
            representativeName: 'John Doe',
        );
        $array = $info->toArray();

        $this->assertSame('Taro Yamada', $array['full_name']);
        $this->assertSame('Company Inc.', $array['company_name']);
        $this->assertSame('John Doe', $array['representative_name']);
    }

    public function testFromArray(): void
    {
        $info = ApplicantInfo::fromArray([
            'full_name' => 'Taro Yamada',
            'company_name' => 'Company Inc.',
            'representative_name' => 'John Doe',
        ]);

        $this->assertSame('Taro Yamada', $info->fullName());
        $this->assertSame('Company Inc.', $info->companyName());
        $this->assertSame('John Doe', $info->representativeName());
    }
}
