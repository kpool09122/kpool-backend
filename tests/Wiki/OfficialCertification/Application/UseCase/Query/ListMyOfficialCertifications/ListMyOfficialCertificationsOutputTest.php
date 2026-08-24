<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications;

use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications\ListMyOfficialCertificationsOutput;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationListItemReadModel;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationOwnerAccountReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListMyOfficialCertificationsOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $certificationId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $ownerAccountIdentifier = StrTestHelper::generateUuid();
        $wikiIdentifier = StrTestHelper::generateUuid();
        $output = new ListMyOfficialCertificationsOutput();

        $output->output([
            new OfficialCertificationListItemReadModel(
                certificationIdentifier: $certificationId,
                resourceType: 'talent',
                translationSetIdentifier: $translationSetIdentifier,
                ownerAccount: new OfficialCertificationOwnerAccountReadModel(
                    accountIdentifier: $ownerAccountIdentifier,
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
                    name: 'Chaeyoung',
                    normalizedName: 'chaeyoung',
                    publishedAt: null,
                    updatedAt: '2024-01-01T00:00:00+00:00',
                    isOfficial: false,
                )],
                status: 'pending',
                requestedAt: '2024-01-01T00:00:00+00:00',
                approvedAt: null,
                rejectedAt: null,
            ),
        ], 1, 1, 1, 10);

        $this->assertSame([
            'officialCertifications' => [[
                'certificationIdentifier' => $certificationId,
                'resourceType' => 'talent',
                'translationSetIdentifier' => $translationSetIdentifier,
                'ownerAccount' => [
                    'accountIdentifier' => $ownerAccountIdentifier,
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
                    'name' => 'Chaeyoung',
                    'normalizedName' => 'chaeyoung',
                    'publishedAt' => null,
                    'updatedAt' => '2024-01-01T00:00:00+00:00',
                ]],
                'status' => 'pending',
                'requestedAt' => '2024-01-01T00:00:00+00:00',
                'approvedAt' => null,
                'rejectedAt' => null,
            ]],
            'current_page' => 1,
            'last_page' => 1,
            'total' => 1,
            'per_page' => 10,
        ], $output->toArray());
    }
}
