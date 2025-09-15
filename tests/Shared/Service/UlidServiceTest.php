<?php

declare(strict_types=1);

namespace Tests\Shared\Service;

use Businesses\Shared\Service\Ulid\UlidValidator;
use PHPUnit\Framework\TestCase;

class UlidServiceTest extends TestCase
{
    /**
     * 正常系：正常なULIDの場合trueを返すこと
     *
     * @return void
     */
    public function testIsValid(): void
    {
        $validUlid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $this->assertTrue(UlidValidator::isValid($validUlid));
    }

    /**
     * 正常系：文字列長が26文字でない場合、falseを返すこと.
     *
     * @return void
     */
    public function isValidWhenNot26CharLength(): void
    {
        $shortUlid = '01ARZ3NDEKTSV4RRFFQ69G5FA';
        $longUlid = '01ARZ3NDEKTSV4RRFFQ69G5FAVX';
        $this->assertFalse(UlidValidator::isValid($shortUlid));
        $this->assertFalse(UlidValidator::isValid($longUlid));
    }

    /**
     * 正常系：無効な文字が含まれる場合、falseを返すこと
     *
     * @return void
     */
    public function testIsValidWithInvalidChar(): void
    {
        $invalidCharUlid = '01ARZ3NDEKTSV4RRFFQ69G5FIL';
        $this->assertFalse(UlidValidator::isValid($invalidCharUlid));
    }

    /**
     * 正常系：最初の文字が7より大きい場合、falseを返すこと
     *
     * @return void
     */
    public function testIsValidWhenFirstCharBeyond7(): void
    {
        $invalidFirstCharUlid = '81ARZ3NDEKTSV4RRFFQ69G5FAV';
        $this->assertFalse(UlidValidator::isValid($invalidFirstCharUlid));
    }
}
