<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Shared\Infrastructure\Service\SvgImageRasterizer;
use Tests\TestCase;

class SvgImageRasterizerTest extends TestCase
{
    /** @return array<string, array{string, int, int}> */
    public static function sizes(): array
    {
        return [
            'small' => ['width="200" height="100"', 200, 100],
            'wide' => ['width="2048" height="1024"', 1024, 512],
            'tall' => ['width="1024" height="2048"', 512, 1024],
            'viewBox' => ['viewBox="0 0 200 100"', 200, 100],
            'viewBox-width' => ['width="100" viewBox="0 0 200 100"', 100, 50],
            'viewBox-height' => ['height="50" viewBox="0 0 200 100"', 100, 50],
            'physical-units' => ['width="2in" height="1in"', 192, 96],
            'points' => ['width="72pt" height="36pt"', 96, 48],
            'large' => ['width="1000000" height="500000"', 1024, 512],
        ];
    }

    #[DataProvider('sizes')]
    public function testRendersSvgWithoutUpscalingAndPreservesTransparency(string $attributes, int $width, int $height): void
    {
        $viewBox = str_contains($attributes, 'viewBox') ? '' : ' viewBox="0 0 200 100"';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" ' . $attributes . $viewBox . '>'
            . '<defs><linearGradient id="red"><stop stop-color="red"/></linearGradient>'
            . '<rect id="shape" width="100" height="100" fill="url(#red)"/></defs><use href="#shape"/></svg>';
        $png = new SvgImageRasterizer()->rasterizeToPng($svg, 1024);

        self::assertIsString($png);
        $size = getimagesizefromstring($png);
        self::assertNotFalse($size);
        self::assertSame('image/png', $size['mime']);
        $image = imagecreatefromstring($png);
        self::assertNotFalse($image);
        self::assertSame($width, imagesx($image));
        self::assertSame($height, imagesy($image));
        $red = imagecolorat($image, 1, (int) floor($height / 2));
        self::assertNotFalse($red);
        self::assertSame(['red' => 255, 'green' => 0, 'blue' => 0, 'alpha' => 0], imagecolorsforindex($image, $red));
        $transparent = imagecolorat($image, $width - 2, (int) floor($height / 2));
        self::assertNotFalse($transparent);
        self::assertSame(127, imagecolorsforindex($image, $transparent)['alpha']);
    }

    public function testDoesNotRenderReferencedFiles(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'svg-reference-');
        self::assertNotFalse($path);
        $reference = imagecreatetruecolor(10, 10);
        imagefill($reference, 0, 0, 0xff0000);
        imagepng($reference, $path);

        try {
            $png = new SvgImageRasterizer()->rasterizeToPng(
                '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10">'
                . '<script>throw new Error("must not execute")</script>'
                . '<image href="file://' . $path . '" width="10" height="10"/>'
                . '<image href="https://example.test/image.png" width="10" height="10"/></svg>',
                1024,
            );
        } finally {
            unlink($path);
        }

        self::assertIsString($png);
        $size = getimagesizefromstring($png);
        self::assertNotFalse($size);
        self::assertSame('image/png', $size['mime']);
        $image = imagecreatefromstring($png);
        self::assertNotFalse($image);
        $color = imagecolorat($image, 1, 1);
        self::assertNotFalse($color);
        self::assertSame(127, imagecolorsforindex($image, $color)['alpha']);
    }

    public function testRendererFailureIsNotAcceptedAsAnImage(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'invalid SVG', exitCode: 1)]);

        self::assertFalse(new SvgImageRasterizer()->rasterizeToPng('<svg xmlns="http://www.w3.org/2000/svg"/>', 1024));
    }

    public function testAcceptsStandardSvgDoctypeWithoutLoadingExternalStylesheet(): void
    {
        $svg = '<?xml version="1.0"?><?xml-stylesheet href="https://example.test/style.css"?>'
            . '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'
            . '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect width="100" height="50" fill="red"/></svg>';

        $png = new SvgImageRasterizer()->rasterizeToPng($svg, 1024);

        self::assertIsString($png);
        $size = getimagesizefromstring($png);
        self::assertNotFalse($size);
        self::assertSame('image/png', $size['mime']);
        $image = imagecreatefromstring($png);
        self::assertNotFalse($image);
        self::assertSame(100, imagesx($image));
        self::assertSame(50, imagesy($image));
    }

    public function testMissingRendererIsReportedAsRuntimeFailure(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'not found', exitCode: 127)]);

        $this->expectException(RuntimeException::class);
        new SvgImageRasterizer()->rasterizeToPng('<svg xmlns="http://www.w3.org/2000/svg"/>', 1024);
    }
}
