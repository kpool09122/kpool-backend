<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

class AutoWikiCreationPayloadTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $language = Language::JAPANESE;
        $resourceType = ResourceType::AGENCY;
        $name = new Name('sample-value');
        $slug = new Slug('ag-sample');
        $agencyIdentifier = new WikiIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $groupIdentifiers = [];
        $talentIdentifiers = [];

        $subject = new AutoWikiCreationPayload($language, $resourceType, $name, $slug, $agencyIdentifier, $groupIdentifiers, $talentIdentifiers);

        $this->assertSame($language, $subject->language());
        $this->assertSame($resourceType, $subject->resourceType());
        $this->assertSame($name, $subject->name());
        $this->assertSame($slug, $subject->slug());
        $this->assertSame($agencyIdentifier, $subject->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $subject->groupIdentifiers());
        $this->assertSame($talentIdentifiers, $subject->talentIdentifiers());
    }

    public function testAllowsAbsentOptionalValues(): void
    {
        $subject = new AutoWikiCreationPayload(Language::JAPANESE, ResourceType::AGENCY, new Name('sample-value'), new Slug('ag-sample'), null, [], []);
        $this->assertNull($subject->agencyIdentifier());
    }
}
