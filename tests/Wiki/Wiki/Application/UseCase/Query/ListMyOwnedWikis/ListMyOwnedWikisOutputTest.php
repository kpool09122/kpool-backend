<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisOutput;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;
use Tests\TestCase;

class ListMyOwnedWikisOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $primary = $this->item('01965bb2-bcc9-7c6f-8b90-89f7f217f101', 'agency', 'Agency');
        $other = $this->item('01965bb2-bcc9-7c6f-8b90-89f7f217f102', 'group', 'Group');

        $output = new ListMyOwnedWikisOutput();
        $output->output(AccountCategory::AGENCY, [$primary], [$other], 1, 3, 21, 10);

        $this->assertSame([
            'accountCategory' => 'agency',
            'primaryOwnedWikis' => [$primary->toArray()],
            'otherOwnedWikis' => [$other->toArray()],
            'current_page' => 1,
            'last_page' => 3,
            'total' => 21,
            'per_page' => 10,
        ], $output->toArray());
    }

    private function item(string $wikiIdentifier, string $resourceType, string $name): WikiListItemReadModel
    {
        return new WikiListItemReadModel(
            wikiIdentifier: $wikiIdentifier,
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            slug: $resourceType . '-slug',
            language: 'ja',
            resourceType: $resourceType,
            version: 1,
            themeColor: null,
            imageIdentifier: null,
            imageUrl: null,
            imageAltText: null,
            isHidden: null,
            name: $name,
            normalizedName: strtolower($name),
            publishedAt: null,
            updatedAt: '2026-08-01T00:00:00+00:00',
        );
    }
}
