<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Support;

use Application\Http\Action\Support\RequestValue;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RequestValueTest extends TestCase
{
    public function testScalarConversionsPreserveRequestValues(): void
    {
        self::assertSame('123', RequestValue::string(123));
        self::assertSame('', RequestValue::string(null));
        self::assertSame(123, RequestValue::integer('123'));
        self::assertSame(0, RequestValue::integer(null));
    }

    public function testStringConversionRejectsNestedInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RequestValue::string(['unexpected']);
    }

    public function testIntegerConversionRejectsNestedInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RequestValue::integer(['unexpected']);
    }

    public function testObjectRetainsOptionalNullAndNestedFields(): void
    {
        $payload = ['name' => 'Example', 'address' => null, 'sections' => [['title' => 'Profile']]];

        self::assertSame($payload, RequestValue::object($payload));
    }

    public function testObjectRejectsNumericFieldNames(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RequestValue::object(['unexpected']);
    }

    public function testObjectCollectionRejectsScalarMembers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RequestValue::objects([['name' => 'Example'], 'unexpected']);
    }

    public function testStringCollectionRejectsNestedMembers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RequestValue::strings(['identifier', ['unexpected']]);
    }
}
