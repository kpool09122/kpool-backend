<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;

class ImagePathTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $path = '/resources/public/images/before.webp';
        $imagePath = new ImagePath($path);
        $this->assertSame($path, (string)$imagePath);
    }

    /**
     * 正常系: 相対パスでインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithRelativePath(): void
    {
        $path = 'images/talents/chaeyoung.jpg';
        $imagePath = new ImagePath($path);
        $this->assertSame($path, (string)$imagePath);
    }

    /**
     * 異常系: 外部URLは拒否されること
     *
     * @return void
     */
    #[DataProvider('externalUrlProvider')]
    public function test__constructThrowsExceptionForExternalUrl(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('External URLs are not allowed for ImagePath.');

        new ImagePath($url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function externalUrlProvider(): array
    {
        return [
            'http' => ['http://example.com/image.jpg'],
            'https' => ['https://example.com/image.jpg'],
            'protocol-relative' => ['//example.com/image.jpg'],
            'HTTP uppercase' => ['HTTP://example.com/image.jpg'],
            'HTTPS uppercase' => ['HTTPS://example.com/image.jpg'],
            'mixed case' => ['HtTpS://example.com/image.jpg'],
        ];
    }
}
