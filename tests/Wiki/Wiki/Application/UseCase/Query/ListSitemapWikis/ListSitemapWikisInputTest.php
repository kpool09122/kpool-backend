<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisInput;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

class ListSitemapWikisInputTest extends TestCase
{
    public function testDefaults(): void
    {
        $input = new ListSitemapWikisInput();
        $this->assertSame(500, $input->perPage());
        $this->assertNull($input->after());
    }

    public function testAccessorsPreserveCursorIdentity(): void
    {
        $after = new WikiIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f101');
        $input = new ListSitemapWikisInput(1000, $after);
        $this->assertSame(1000, $input->perPage());
        $this->assertSame($after, $input->after());
        $this->assertSame(1, (new ListSitemapWikisInput(1))->perPage());
    }

    #[DataProvider('invalidPerPageProvider')]
    public function testRejectsUnboundedPageSizes(int $perPage): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListSitemapWikisInput($perPage);
    }

    /** @return array<string, array{int}> */
    public static function invalidPerPageProvider(): array
    {
        return ['zero' => [0], 'negative' => [-1], 'over maximum' => [1001]];
    }
}
