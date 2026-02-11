<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\SaveVideoLinksInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\VideoLinkData;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SaveVideoLinksInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $videoLinks = [
            new VideoLinkData(
                url: new ExternalContentLink('https://www.youtube.com/watch?v=test1'),
                videoUsage: VideoUsage::MUSIC_VIDEO,
                title: 'テスト動画1',
                displayOrder: 1,
            ),
            new VideoLinkData(
                url: new ExternalContentLink('https://www.youtube.com/watch?v=test2'),
                videoUsage: VideoUsage::LIVE,
                title: 'テスト動画2',
                displayOrder: 2,
            ),
        ];

        $input = new SaveVideoLinksInput(
            $principalIdentifier,
            $resourceType,
            $resourceIdentifier,
            $videoLinks,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($resourceType, $input->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame($videoLinks, $input->videoLinks());
    }

    /**
     * 正常系: 空のvideoLinksでインスタンスが生成されること
     *
     * @return void
     */
    public function testConstructWithEmptyVideoLinks(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::SONG;
        $resourceIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $videoLinks = [];

        $input = new SaveVideoLinksInput(
            $principalIdentifier,
            $resourceType,
            $resourceIdentifier,
            $videoLinks,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($resourceType, $input->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame([], $input->videoLinks());
    }
}
