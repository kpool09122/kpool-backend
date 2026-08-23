<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResource;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertificationsOutput;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SyncOwnedWikiCertificationsOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyResultByDefault(): void
    {
        $output = new SyncOwnedWikiCertificationsOutput();

        $this->assertSame([
            'approved' => [],
            'rejected' => [],
            'unchanged' => [],
        ], $output->toArray());
    }

    public function testToArrayConvertsResourcesAtOutputBoundary(): void
    {
        $approvedId = StrTestHelper::generateUuid();
        $rejectedId = StrTestHelper::generateUuid();
        $unchangedId = StrTestHelper::generateUuid();
        $output = new SyncOwnedWikiCertificationsOutput();

        $output->setResult(
            [
                new SyncableOwnedWikiResource(ResourceType::GROUP, new TranslationSetIdentifier($approvedId)),
            ],
            [
                new SyncableOwnedWikiResource(ResourceType::SONG, new TranslationSetIdentifier($rejectedId)),
            ],
            [
                new SyncableOwnedWikiResource(ResourceType::GROUP, new TranslationSetIdentifier($unchangedId)),
            ],
        );

        $this->assertSame([
            'approved' => [
                [
                    'resourceType' => 'group',
                    'translationSetIdentifier' => $approvedId,
                ],
            ],
            'rejected' => [
                [
                    'resourceType' => 'song',
                    'translationSetIdentifier' => $rejectedId,
                ],
            ],
            'unchanged' => [
                [
                    'resourceType' => 'group',
                    'translationSetIdentifier' => $unchangedId,
                ],
            ],
        ], $output->toArray());
    }
}
