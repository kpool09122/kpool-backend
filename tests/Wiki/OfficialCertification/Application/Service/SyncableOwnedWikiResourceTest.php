<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class SyncableOwnedWikiResourceTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $resourceType = ResourceType::AGENCY;
        $translationSetIdentifier = new TranslationSetIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new SyncableOwnedWikiResource($resourceType, $translationSetIdentifier);

        $this->assertSame($resourceType, $subject->resourceType());
        $this->assertSame($translationSetIdentifier, $subject->translationSetIdentifier());
    }
}
