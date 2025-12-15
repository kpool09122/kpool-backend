<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Tests\TestCase;

class NormalizationServiceTest extends TestCase
{
    /**
     * 正常系: 漢字がひらがなに変換されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeJapaneseKanji(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('漢字', Language::JAPANESE);
        $this->assertSame('かんじ', $result);
    }

    /**
     * 正常系: カタカナがひらがなに変換されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeJapaneseKatakana(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('カタカナ', Language::JAPANESE);
        $this->assertSame('かたかな', $result);
    }

    /**
     * 正常系: ひらがなはそのままであること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeJapaneseHiragana(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('ひらがな', Language::JAPANESE);
        $this->assertSame('ひらがな', $result);
    }

    /**
     * 正常系: 日本語の時もアルファベットは小文字に変換されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeJapaneseAlphabet(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('ALPHABET', Language::JAPANESE);
        $this->assertSame('alphabet', $result);
    }

    /**
     * 正常系: 漢字・ひらがな・カタカナ・アルファベットの混合文字列が正規化されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeJapaneseMixed(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('漢字とカタカナとHiragana', Language::JAPANESE);
        $this->assertSame('かんじとかたかなとhiragana', $result);
    }

    /**
     * 正常系: ハングルが初声に変換されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeKoreanHangul(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('한국어', Language::KOREAN);
        $this->assertSame('ㅎㄱㅇ', $result);
    }

    /**
     * 正常系: ハングル単語が初声に変換されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeKoreanWord(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('사랑', Language::KOREAN);
        $this->assertSame('ㅅㄹ', $result);
    }

    /**
     * 正常系: ハングル文章が初声に変換されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeKoreanSentence(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('안녕하세요', Language::KOREAN);
        $this->assertSame('ㅇㄴㅎㅅㅇ', $result);
    }

    /**
     * 正常系: 韓国語の数字は変換されないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeKoreanNumber(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('123', Language::KOREAN);
        $this->assertSame('123', $result);
    }

    /**
     * 正常系: 韓国語のアルファベットは小文字に変換されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeKoreanAlphabet(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('ABC', Language::KOREAN);
        $this->assertSame('abc', $result);
    }

    /**
     * 正常系: 英語の大文字が小文字に変換されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeEnglishUppercase(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('HELLO', Language::ENGLISH);
        $this->assertSame('hello', $result);
    }

    /**
     * 正常系: 英語の小文字はそのままであること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeEnglishLowercase(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('hello', Language::ENGLISH);
        $this->assertSame('hello', $result);
    }

    /**
     * 正常系: 英語の大文字小文字混合が小文字に変換されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeEnglishMixed(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $result = $service->normalize('HeLLo WoRLd', Language::ENGLISH);
        $this->assertSame('hello world', $result);
    }

    /**
     * 正常系: 空文字列が正規化できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNormalizeEmptyString(): void
    {
        $service = $this->app->make(NormalizationServiceInterface::class);
        $this->assertSame('', $service->normalize('', Language::JAPANESE));
        $this->assertSame('', $service->normalize('', Language::KOREAN));
        $this->assertSame('', $service->normalize('', Language::ENGLISH));
    }
}
