<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

class VideoUsageTest extends TestCase
{
    /**
     * 正常系: MUSIC_VIDEOが正しく定義されていること.
     */
    public function testMusicVideoCase(): void
    {
        $usage = VideoUsage::MUSIC_VIDEO;

        $this->assertSame('music_video', $usage->value);
    }

    /**
     * 正常系: LIVEが正しく定義されていること.
     */
    public function testLiveCase(): void
    {
        $usage = VideoUsage::LIVE;

        $this->assertSame('live', $usage->value);
    }

    /**
     * 正常系: INTERVIEWが正しく定義されていること.
     */
    public function testInterviewCase(): void
    {
        $usage = VideoUsage::INTERVIEW;

        $this->assertSame('interview', $usage->value);
    }

    /**
     * 正常系: BEHIND_THE_SCENESが正しく定義されていること.
     */
    public function testBehindTheScenesCase(): void
    {
        $usage = VideoUsage::BEHIND_THE_SCENES;

        $this->assertSame('behind_the_scenes', $usage->value);
    }

    /**
     * 正常系: COVERが正しく定義されていること.
     */
    public function testCoverCase(): void
    {
        $usage = VideoUsage::COVER;

        $this->assertSame('cover', $usage->value);
    }

    /**
     * 正常系: COLLABORATIONが正しく定義されていること.
     */
    public function testCollaborationCase(): void
    {
        $usage = VideoUsage::COLLABORATION;

        $this->assertSame('collaboration', $usage->value);
    }

    /**
     * 正常系: SHORTが正しく定義されていること.
     */
    public function testShortCase(): void
    {
        $usage = VideoUsage::SHORT;

        $this->assertSame('short', $usage->value);
    }

    /**
     * 正常系: OTHERが正しく定義されていること.
     */
    public function testOtherCase(): void
    {
        $usage = VideoUsage::OTHER;

        $this->assertSame('other', $usage->value);
    }

    /**
     * 正常系: fromメソッドで文字列からenumを生成できること.
     */
    public function testFromString(): void
    {
        $this->assertSame(VideoUsage::MUSIC_VIDEO, VideoUsage::from('music_video'));
        $this->assertSame(VideoUsage::LIVE, VideoUsage::from('live'));
        $this->assertSame(VideoUsage::INTERVIEW, VideoUsage::from('interview'));
        $this->assertSame(VideoUsage::BEHIND_THE_SCENES, VideoUsage::from('behind_the_scenes'));
        $this->assertSame(VideoUsage::COVER, VideoUsage::from('cover'));
        $this->assertSame(VideoUsage::COLLABORATION, VideoUsage::from('collaboration'));
        $this->assertSame(VideoUsage::SHORT, VideoUsage::from('short'));
        $this->assertSame(VideoUsage::OTHER, VideoUsage::from('other'));
    }

    /**
     * 正常系: 自動収集用のケースが正しく定義されていること.
     */
    public function testAutoCollectedCases(): void
    {
        $this->assertSame('youtube_auto_view_count', VideoUsage::YOUTUBE_AUTO_VIEW_COUNT->value);
        $this->assertSame('youtube_auto_like_count', VideoUsage::YOUTUBE_AUTO_LIKE_COUNT->value);
        $this->assertSame('youtube_auto_recent_popular', VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR->value);
    }

    /**
     * 正常系: 手動追加の用途がisManual()でtrueを返すこと.
     */
    public function testIsManualReturnsTrue(): void
    {
        $manualUsages = [
            VideoUsage::MUSIC_VIDEO,
            VideoUsage::LIVE,
            VideoUsage::INTERVIEW,
            VideoUsage::BEHIND_THE_SCENES,
            VideoUsage::COVER,
            VideoUsage::COLLABORATION,
            VideoUsage::SHORT,
            VideoUsage::OTHER,
        ];

        foreach ($manualUsages as $usage) {
            $this->assertTrue($usage->isManual(), "{$usage->value} should be manual");
        }
    }

    /**
     * 正常系: 自動収集の用途がisManual()でfalseを返すこと.
     */
    public function testIsManualReturnsFalse(): void
    {
        $autoCollectedUsages = [
            VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
            VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
            VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR,
        ];

        foreach ($autoCollectedUsages as $usage) {
            $this->assertFalse($usage->isManual(), "{$usage->value} should not be manual");
        }
    }

    /**
     * 正常系: 自動収集の用途がisAutoCollected()でtrueを返すこと.
     */
    public function testIsAutoCollectedReturnsTrue(): void
    {
        $autoCollectedUsages = [
            VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
            VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
            VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR,
        ];

        foreach ($autoCollectedUsages as $usage) {
            $this->assertTrue($usage->isAutoCollected(), "{$usage->value} should be auto collected");
        }
    }

    /**
     * 正常系: 手動追加の用途がisAutoCollected()でfalseを返すこと.
     */
    public function testIsAutoCollectedReturnsFalse(): void
    {
        $manualUsages = [
            VideoUsage::MUSIC_VIDEO,
            VideoUsage::LIVE,
            VideoUsage::INTERVIEW,
            VideoUsage::BEHIND_THE_SCENES,
            VideoUsage::COVER,
            VideoUsage::COLLABORATION,
            VideoUsage::SHORT,
            VideoUsage::OTHER,
        ];

        foreach ($manualUsages as $usage) {
            $this->assertFalse($usage->isAutoCollected(), "{$usage->value} should not be auto collected");
        }
    }
}
