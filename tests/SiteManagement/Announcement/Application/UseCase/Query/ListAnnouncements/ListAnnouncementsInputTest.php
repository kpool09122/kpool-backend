<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsInput;

class ListAnnouncementsInputTest extends TestCase
{
    public function testDefaultsAndCustomOptions(): void
    {
        $defaults = new ListAnnouncementsInput(Language::JAPANESE);
        $this->assertSame(50, $defaults->perPage());
        $this->assertSame('desc', $defaults->order());
        $this->assertSame('published_date', $defaults->sort());
        $this->assertSame('', $defaults->searchWords());
        $input = new ListAnnouncementsInput(Language::KOREAN, 100, 'asc', 'title', 'query');
        $this->assertSame(Language::KOREAN, $input->language());
        $this->assertSame(100, $input->perPage());
        $this->assertSame('asc', $input->order());
        $this->assertSame('title', $input->sort());
        $this->assertSame('query', $input->searchWords());
    }

    #[DataProvider('invalidPagination')]
    public function testRejectsInvalidPagination(int $perPage): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListAnnouncementsInput(Language::JAPANESE, $perPage);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidPagination(): iterable
    {
        yield 'zero per page' => [0];
        yield 'negative per page' => [-1];
        yield 'too many per page' => [101];
    }
}
