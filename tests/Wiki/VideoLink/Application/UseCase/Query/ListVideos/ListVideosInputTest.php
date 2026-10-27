<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query\ListVideos;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInput;

class ListVideosInputTest extends TestCase
{
    public function testWatchConditionsAreIndependentAndDefaultToFalse(): void
    {
        foreach ([false, true] as $recent) {
            foreach ([false, true] as $sort) {
                $input = new ListVideosInput(new Slug('tl-test'), Language::JAPANESE, ResourceType::TALENT, 29, 30, $recent, $sort);
                self::assertSame($recent, $input->recentWatchRequestsOnly());
                self::assertSame($sort, $input->sortByWatchRequests());
                self::assertSame(29, $input->minDurationSeconds());
                self::assertSame(30, $input->maxDurationSeconds());
            }
        }
        $input = new ListVideosInput(new Slug('tl-test'), Language::JAPANESE, ResourceType::TALENT);
        self::assertFalse($input->recentWatchRequestsOnly());
        self::assertFalse($input->sortByWatchRequests());
    }

    public function testPreservesDurationBounds(): void
    {
        $input = new ListVideosInput(new Slug('gr-test'), Language::JAPANESE, ResourceType::GROUP, 600, 1200);
        $this->assertSame(600, $input->minDurationSeconds());
        $this->assertSame(1200, $input->maxDurationSeconds());
    }

    #[DataProvider('invalidBounds')]
    public function testRejectsInvalidDurationBounds(?int $minimum, ?int $maximum): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListVideosInput(new Slug('gr-test'), Language::JAPANESE, ResourceType::GROUP, $minimum, $maximum);
    }

    /** @return list<array{?int, ?int}> */
    public static function invalidBounds(): array
    {
        return [[-1, null], [null, -1], [2147483648, null], [null, 2147483648], [1200, 600]];
    }

    public function testPreservesInputValueObjects(): void
    {
        $slug = new Slug('gr-test');
        $input = new ListVideosInput($slug, Language::JAPANESE, ResourceType::GROUP);
        $this->assertNull($input->minDurationSeconds());
        $this->assertNull($input->maxDurationSeconds());
        $this->assertSame($slug, $input->slug());
        $this->assertSame(Language::JAPANESE, $input->language());
        $this->assertSame(ResourceType::GROUP, $input->resourceType());
    }
}
