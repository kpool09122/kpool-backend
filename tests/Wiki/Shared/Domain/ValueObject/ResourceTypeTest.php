<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

class ResourceTypeTest extends TestCase
{
    #[DataProvider('slugPrefixProvider')]
    public function testSlugPrefix(ResourceType $resourceType, string $expected): void
    {
        $this->assertSame($expected, $resourceType->slugPrefix());
    }

    /**
     * @return array<string, array{ResourceType, string}>
     */
    public static function slugPrefixProvider(): array
    {
        return [
            'agency' => [ResourceType::AGENCY, 'ag'],
            'group' => [ResourceType::GROUP, 'gr'],
            'song' => [ResourceType::SONG, 'sg'],
            'talent' => [ResourceType::TALENT, 'tl'],
        ];
    }

    #[DataProvider('slugResourceTypeProvider')]
    public function testFromSlug(string $slug, ResourceType $expected): void
    {
        $this->assertSame($expected, ResourceType::fromSlug(new Slug($slug)));
    }

    /**
     * @return array<string, array{string, ResourceType}>
     */
    public static function slugResourceTypeProvider(): array
    {
        return [
            'agency' => ['ag-jyp-entertainment', ResourceType::AGENCY],
            'group' => ['gr-twice', ResourceType::GROUP],
            'song' => ['sg-signal', ResourceType::SONG],
            'talent' => ['tl-chaeyoung', ResourceType::TALENT],
        ];
    }

    public function testSlugPrefixThrowsForImage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IMAGE resource type does not support wiki slug prefixes.');

        ResourceType::IMAGE->slugPrefix();
    }
}
