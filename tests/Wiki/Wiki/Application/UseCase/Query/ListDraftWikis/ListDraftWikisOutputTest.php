<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis;

use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiListItemReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisOutput;
use Tests\TestCase;

class ListDraftWikisOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $item = new DraftWikiListItemReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            publishedWikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            slug: 'tl-chaeyoung',
            language: 'ko',
            resourceType: 'talent',
            themeColor: '#FE5F8F',
            status: ApprovalStatus::Pending->value,
            name: 'Chaeyoung',
            normalizedName: 'chaeyoung',
            editedAt: '2026-05-01T00:00:00+00:00',
            updatedAt: '2026-05-02T00:00:00+00:00',
            approvedAt: null,
            translatedAt: null,
            mergedAt: null,
        );

        $output = new ListDraftWikisOutput();
        $output->output([$item], 1, 3, 5, 2);

        $this->assertSame([
            'wikis' => [$item->toArray()],
            'current_page' => 1,
            'last_page' => 3,
            'total' => 5,
            'per_page' => 2,
        ], $output->toArray());
    }
}
