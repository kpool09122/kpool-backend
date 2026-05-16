<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiListItemReadModel;
use Tests\TestCase;

class DraftWikiListItemReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $readModel = new DraftWikiListItemReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            publishedWikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            slug: 'tl-chaeyoung',
            language: 'ko',
            resourceType: 'talent',
            themeColor: '#FE5F8F',
            status: ApprovalStatus::UnderReview->value,
            name: 'Chaeyoung',
            normalizedName: 'chaeyoung',
            editedAt: '2026-05-01T00:00:00+00:00',
            updatedAt: '2026-05-02T00:00:00+00:00',
            approvedAt: '2026-05-03T00:00:00+00:00',
            translatedAt: '2026-05-04T00:00:00+00:00',
            mergedAt: '2026-05-05T00:00:00+00:00',
        );

        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'publishedWikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            'slug' => 'tl-chaeyoung',
            'language' => 'ko',
            'resourceType' => 'talent',
            'themeColor' => '#FE5F8F',
            'status' => ApprovalStatus::UnderReview->value,
            'name' => 'Chaeyoung',
            'normalizedName' => 'chaeyoung',
            'editedAt' => '2026-05-01T00:00:00+00:00',
            'updatedAt' => '2026-05-02T00:00:00+00:00',
            'approvedAt' => '2026-05-03T00:00:00+00:00',
            'translatedAt' => '2026-05-04T00:00:00+00:00',
            'mergedAt' => '2026-05-05T00:00:00+00:00',
        ], $readModel->toArray());
    }
}
