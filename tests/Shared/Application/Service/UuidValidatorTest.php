<?php

declare(strict_types=1);

namespace Tests\Shared\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Shared\Application\Service\Uuid\UuidValidator;

class UuidValidatorTest extends TestCase
{
    /**
     * 正常系：正常なUUIDv7の場合trueを返すこと
     *
     * @return void
     */
    public function testIsValid(): void
    {
        $validUuid = '018e3f5c-9a7b-7def-8abc-1234567890ab';
        $this->assertTrue(UuidValidator::isValid($validUuid));
    }

    /**
     * 正常系：大文字のUUIDv7でもtrueを返すこと
     *
     * @return void
     */
    public function testIsValidWithUpperCase(): void
    {
        $validUuid = '018E3F5C-9A7B-7DEF-8ABC-1234567890AB';
        $this->assertTrue(UuidValidator::isValid($validUuid));
    }

    /**
     * 正常系：文字列長が36文字でない場合、falseを返すこと.
     *
     * @return void
     */
    public function testIsValidWhenNot36CharLength(): void
    {
        $shortUuid = '018e3f5c-9a7b-7def-8abc-1234567890a';
        $longUuid = '018e3f5c-9a7b-7def-8abc-1234567890abc';
        $this->assertFalse(UuidValidator::isValid($shortUuid));
        $this->assertFalse(UuidValidator::isValid($longUuid));
    }

    /**
     * 正常系：無効な文字が含まれる場合、falseを返すこと
     *
     * @return void
     */
    public function testIsValidWithInvalidChar(): void
    {
        // 'g' は16進数で無効
        $invalidCharUuid = '018e3f5c-9a7b-7def-8abc-1234567890ag';
        $this->assertFalse(UuidValidator::isValid($invalidCharUuid));
    }

    /**
     * 正常系：バージョン番号が7でない場合、falseを返すこと
     *
     * @return void
     */
    public function testIsValidWhenVersionIsNot7(): void
    {
        // バージョン4のUUID（13文字目が'4'）
        $uuidV4 = '018e3f5c-9a7b-4def-8abc-1234567890ab';
        $this->assertFalse(UuidValidator::isValid($uuidV4));
    }

    /**
     * 正常系：バリアントが不正な場合、falseを返すこと
     *
     * @return void
     */
    public function testIsValidWhenVariantIsInvalid(): void
    {
        // バリアント（17文字目）が'0'で不正（8, 9, a, b のいずれかであるべき）
        $invalidVariantUuid = '018e3f5c-9a7b-7def-0abc-1234567890ab';
        $this->assertFalse(UuidValidator::isValid($invalidVariantUuid));
    }

    /**
     * 正常系：ハイフンの位置が不正な場合、falseを返すこと
     *
     * @return void
     */
    public function testIsValidWhenHyphenPositionIsInvalid(): void
    {
        $invalidHyphenUuid = '018e3f5c9a7b-7def-8abc-1234567890ab';
        $this->assertFalse(UuidValidator::isValid($invalidHyphenUuid));
    }
}
