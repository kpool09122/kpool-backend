<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInput;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\StrTestHelper;

class ListMyVideoLinkLikesInputTest extends TestCase
{
    public function testPreservesPrincipalAndIdentifiersAtMaximumBatchSize(): void
    {
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $ids = array_map(static fn (): VideoLinkIdentifier => new VideoLinkIdentifier(StrTestHelper::generateUuid()), range(1, 100));
        $input = new ListMyVideoLinkLikesInput($principal, $ids);
        self::assertSame($principal, $input->principalIdentifier());
        self::assertSame($ids, $input->videoLinkIdentifiers());
        $single = new ListMyVideoLinkLikesInput($principal, [$ids[0]]);
        self::assertSame([$ids[0]], $single->videoLinkIdentifiers());
    }

    public function testOmittedIdentifiersEnableListingWithDefaultPagination(): void
    {
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ListMyVideoLinkLikesInput($principal);
        self::assertSame($principal, $input->principalIdentifier());
        self::assertNull($input->videoLinkIdentifiers());
        self::assertSame(1, $input->page());
        self::assertSame(20, $input->perPage());
        $custom = new ListMyVideoLinkLikesInput($principal, null, 3, 100);
        self::assertSame(3, $custom->page());
        self::assertSame(100, $custom->perPage());
    }

    /** @return array<string, array{int, int}> */
    public static function invalidPagination(): array
    {
        return ['zero page' => [0, 20], 'negative page' => [-1, 20], 'zero size' => [1, 0], 'negative size' => [1, -1], 'oversize' => [1, 101]];
    }

    #[DataProvider('invalidPagination')]
    public function testRejectsInvalidPagination(int $page, int $perPage): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListMyVideoLinkLikesInput(new PrincipalIdentifier(StrTestHelper::generateUuid()), null, $page, $perPage);
    }

    /** @return array<string, array{int}> */
    public static function invalidSizes(): array
    {
        return ['empty' => [0], 'over limit' => [101]];
    }

    #[DataProvider('invalidSizes')]
    public function testRejectsInvalidBatchSize(int $size): void
    {
        $ids = $size === 0 ? [] : array_fill(0, $size, new VideoLinkIdentifier(StrTestHelper::generateUuid()));
        $this->expectException(InvalidArgumentException::class);
        new ListMyVideoLinkLikesInput(new PrincipalIdentifier(StrTestHelper::generateUuid()), $ids);
    }
}
