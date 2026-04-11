<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RejectImage;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\UseCase\Command\RejectImage\RejectImageOutput;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectImageOutputTest extends TestCase
{
    /**
     * 正常系: DraftImageがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    public function testToArrayWithDraftImage(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $draftImage = new DraftImage(
            $imageIdentifier,
            null,
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.png'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Alt text',
            ApprovalStatus::Rejected,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $output = new RejectImageOutput();
        $output->setDraftImage($draftImage);

        $result = $output->toArray();

        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame('talent', $result['resourceType']);
        $this->assertSame('profile', $result['imageUsage']);
        $this->assertSame('rejected', $result['status']);
    }

    /**
     * 正常系: DraftImageがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutDraftImage(): void
    {
        $output = new RejectImageOutput();

        $result = $output->toArray();

        $this->assertSame([
            'imageIdentifier' => null,
            'resourceType' => null,
            'imageUsage' => null,
            'status' => null,
        ], $result);
    }
}
