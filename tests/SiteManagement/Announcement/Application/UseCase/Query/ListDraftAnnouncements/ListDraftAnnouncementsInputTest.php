<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsInput;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

class ListDraftAnnouncementsInputTest extends TestCase
{
    public function testDefaultsAndCustomOptions(): void
    {
        $defaults = new ListDraftAnnouncementsInput(new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000009'));
        $this->assertSame(50, $defaults->perPage());
        $this->assertSame('desc', $defaults->order());
        $this->assertSame('created_at', $defaults->sort());
        $this->assertSame('', $defaults->searchWords());
        $input = new ListDraftAnnouncementsInput(new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000008'), 100, 'asc', 'created_at', 'query');
        $this->assertSame('019c9b4c-0000-7000-8000-000000000008', (string) $input->principalIdentifier());
        $this->assertSame(100, $input->perPage());
        $this->assertSame('asc', $input->order());
        $this->assertSame('created_at', $input->sort());
        $this->assertSame('query', $input->searchWords());
    }

    #[DataProvider('invalidPagination')]
    public function testRejectsInvalidPagination(int $perPage): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListDraftAnnouncementsInput(new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000009'), $perPage);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidPagination(): iterable
    {
        yield 'zero per page' => [0];
        yield 'negative per page' => [-1];
        yield 'too many per page' => [101];
    }
}
