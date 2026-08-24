<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Query;

use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationListItemReadModel;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationOwnerAccountReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class OfficialCertificationListItemReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $certificationIdentifier = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $accountIdentifier = StrTestHelper::generateUuid();
        $wikiIdentifier = StrTestHelper::generateUuid();
        $readModel = new OfficialCertificationListItemReadModel(
            certificationIdentifier: $certificationIdentifier,
            resourceType: 'talent',
            translationSetIdentifier: $translationSetIdentifier,
            ownerAccount: new OfficialCertificationOwnerAccountReadModel(
                accountIdentifier: $accountIdentifier,
                email: 'owner@example.com',
                type: 'corporate',
                name: 'Owner Account',
                status: 'active',
                category: 'talent',
            ),
            wikis: [new WikiListItemReadModel(
                wikiIdentifier: $wikiIdentifier,
                translationSetIdentifier: $translationSetIdentifier,
                slug: 'tl-chaeyoung',
                language: 'ko',
                resourceType: 'talent',
                version: 1,
                themeColor: null,
                imageIdentifier: null,
                imageUrl: null,
                imageAltText: null,
                isHidden: null,
                name: '채영',
                normalizedName: 'chaeyoung',
                publishedAt: null,
                updatedAt: '2024-01-01T00:00:00+00:00',
                isOfficial: false,
            )],
            status: 'pending',
            requestedAt: '2024-01-01T00:00:00+00:00',
            approvedAt: null,
            rejectedAt: null,
        );

        $this->assertSame([
            'certificationIdentifier' => $certificationIdentifier,
            'resourceType' => 'talent',
            'translationSetIdentifier' => $translationSetIdentifier,
            'ownerAccount' => [
                'accountIdentifier' => $accountIdentifier,
                'email' => 'owner@example.com',
                'type' => 'corporate',
                'name' => 'Owner Account',
                'status' => 'active',
                'category' => 'talent',
            ],
            'wikis' => [[
                'wikiIdentifier' => $wikiIdentifier,
                'translationSetIdentifier' => $translationSetIdentifier,
                'slug' => 'tl-chaeyoung',
                'language' => 'ko',
                'resourceType' => 'talent',
                'version' => 1,
                'isOfficial' => false,
                'themeColor' => null,
                'fontStyle' => null,
                'title' => null,
                'metaDescription' => null,
                'keywords' => null,
                'imageIdentifier' => null,
                'imageUrl' => null,
                'imageAltText' => null,
                'isHidden' => null,
                'name' => '채영',
                'normalizedName' => 'chaeyoung',
                'publishedAt' => null,
                'updatedAt' => '2024-01-01T00:00:00+00:00',
            ]],
            'status' => 'pending',
            'requestedAt' => '2024-01-01T00:00:00+00:00',
            'approvedAt' => null,
            'rejectedAt' => null,
        ], $readModel->toArray());
    }

    public function testToArrayWithNullableRelations(): void
    {
        $readModel = new OfficialCertificationListItemReadModel(
            certificationIdentifier: StrTestHelper::generateUuid(),
            resourceType: 'talent',
            translationSetIdentifier: StrTestHelper::generateUuid(),
            ownerAccount: null,
            wikis: [],
            status: 'pending',
            requestedAt: '2024-01-01T00:00:00+00:00',
            approvedAt: null,
            rejectedAt: null,
        );

        $array = $readModel->toArray();
        $this->assertNull($array['ownerAccount']);
        $this->assertSame([], $array['wikis']);
    }
}
