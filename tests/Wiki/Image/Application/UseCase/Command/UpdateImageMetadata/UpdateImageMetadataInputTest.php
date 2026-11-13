<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata\UpdateImageMetadataInput;
use Source\Wiki\Image\Domain\ValueObject\ImageAuthor;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdateImageMetadataInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new UpdateImageMetadataInput(
            $imageIdentifier,
            $principalIdentifier,
            "Source",
            new ImageAuthor("Creator"),
            ImageLicense::CC_BY,
            "Alt",
            2,
        );

        $this->assertSame((string) $imageIdentifier, (string) $input->imageIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame('Source', $input->sourceName());
        $this->assertSame('Creator', (string) $input->author());
        $this->assertSame(ImageLicense::CC_BY, $input->license());
        $this->assertSame('Alt', $input->altText());
        $this->assertSame(2, $input->displayOrder());
    }

    /** @return iterable<string, array{string, string, int}> */
    public static function invalidMetadata(): iterable
    {
        yield 'blank source' => [' ', '', 0];
        yield 'long source' => [str_repeat('あ', 256), '', 0];
        yield 'long alternative text' => ['Source', str_repeat('あ', 513), 0];
        yield 'negative order' => ['Source', '', -1];
        yield 'overflow order' => ['Source', '', 2147483648];
    }

    #[DataProvider('invalidMetadata')]
    public function testRejectsValuesOutsideStorageLimits(string $sourceName, string $altText, int $displayOrder): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UpdateImageMetadataInput(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $sourceName,
            new ImageAuthor('Creator'),
            ImageLicense::CC_BY,
            $altText,
            $displayOrder,
        );
    }

    public function testAcceptsStorageBoundaries(): void
    {
        $input = new UpdateImageMetadataInput(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            str_repeat('あ', 255),
            new ImageAuthor('Creator'),
            ImageLicense::CC_BY,
            str_repeat('あ', 512),
            2147483647,
        );
        self::assertSame(2147483647, $input->displayOrder());
        self::assertSame(512, mb_strlen($input->altText()));
    }
}
