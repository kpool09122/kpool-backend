<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Song;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\ReleaseDate;

class ReleaseDateTest extends TestCase
{
    public function testPreservesReleaseDate(): void
    {
        $date = new DateTimeImmutable('2026-10-03');
        $this->assertSame($date, (new ReleaseDate($date))->value());
    }
}
