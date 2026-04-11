<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\UnhideImage;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\UseCase\Command\UnhideImage\UnhideImageOutput;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UnhideImageOutputTest extends TestCase
{
    /**
     * 正常系: Imageがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    public function testToArrayWithImage(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $image = new Image(
            $imageIdentifier,
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.png'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Alt text',
            false,
            null,
            null,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            null,
            null,
            null,
            null,
        );

        $output = new UnhideImageOutput();
        $output->setImage($image);

        $result = $output->toArray();

        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame('talent', $result['resourceType']);
        $this->assertSame('profile', $result['imageUsage']);
        $this->assertFalse($result['isHidden']);
    }

    /**
     * 正常系: Imageがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutImage(): void
    {
        $output = new UnhideImageOutput();

        $result = $output->toArray();

        $this->assertSame([
            'imageIdentifier' => null,
            'resourceType' => null,
            'imageUsage' => null,
            'isHidden' => null,
        ], $result);
    }
}
