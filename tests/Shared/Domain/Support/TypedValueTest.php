<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\Support;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\Support\TypedValue;
use UnexpectedValueException;

class TypedValueTest extends TestCase
{
    public function testPreservesStringValuesAndArrayKeys(): void
    {
        self::assertSame(['id' => '001', 4 => '0'], TypedValue::stringArray(['id' => '001', 4 => '0']));
        self::assertSame(['id' => '001', 'optional' => null], TypedValue::stringMap(['id' => '001', 'optional' => null]));
        self::assertNull(TypedValue::nullableString(null));
        self::assertSame('001', TypedValue::nullableStringOrInt('001'));
        self::assertSame(1, TypedValue::nullableStringOrInt(1));
        self::assertSame(0, TypedValue::int(0));
        self::assertSame(170, TypedValue::numericInt('170'));
    }

    public function testRejectsNonIntegerNumericString(): void
    {
        $this->expectException(UnexpectedValueException::class);
        TypedValue::numericInt('170.5');
    }

    public function testRejectsScalarCoercion(): void
    {
        $this->expectException(UnexpectedValueException::class);
        TypedValue::string(123);
    }

    public function testRejectsMalformedNestedValues(): void
    {
        $this->expectException(UnexpectedValueException::class);
        TypedValue::stringMap(['identity_id' => ['unexpected']]);
    }

    public function testRejectsNonStringMapKeys(): void
    {
        $this->expectException(UnexpectedValueException::class);
        TypedValue::stringMap([1 => 'value']);
    }

    public function testRejectsNonArrayPayloads(): void
    {
        $this->expectException(UnexpectedValueException::class);
        TypedValue::array('invalid');
    }

    public function testRejectsNumericStringAsInteger(): void
    {
        $this->expectException(UnexpectedValueException::class);
        TypedValue::int('123');
    }
}
