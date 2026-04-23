<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiSnapshotIdentifier;
use Tests\Helper\StrTestHelper;

class WikiSnapshotTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $snapshotIdentifier = new WikiSnapshotIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('gr-test-slug');
        $language = Language::KOREAN;
        $resourceType = ResourceType::AGENCY;
        $basic = new AgencyBasic(
            name: new Name('JYP엔터테인먼트'),
            normalizedName: 'jyp',
            ceo: new CEO('정욱'),
            normalizedCeo: 'jungwook',
            foundedIn: null,
            parentAgencyIdentifier: null,
            status: null,
            logoImageIdentifier: null,
            officialWebsite: null,
            socialLinks: [],
        );
        $sections = new SectionContentCollection();
        $themeColor = new Color('#FF5733');
        $version = new Version(1);
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2025-01-01');
        $translatedAt = new DateTimeImmutable('2025-01-02');
        $approvedAt = new DateTimeImmutable('2025-01-03');
        $createdAt = new DateTimeImmutable('2025-01-04');

        $snapshot = new WikiSnapshot(
            snapshotIdentifier: $snapshotIdentifier,
            wikiIdentifier: $wikiIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            slug: $slug,
            language: $language,
            resourceType: $resourceType,
            basic: $basic,
            sections: $sections,
            themeColor: $themeColor,
            version: $version,
            editorIdentifier: $editorIdentifier,
            approverIdentifier: $approverIdentifier,
            mergerIdentifier: $mergerIdentifier,
            sourceEditorIdentifier: $sourceEditorIdentifier,
            mergedAt: $mergedAt,
            translatedAt: $translatedAt,
            approvedAt: $approvedAt,
            createdAt: $createdAt,
        );

        $this->assertSame($snapshotIdentifier, $snapshot->snapshotIdentifier());
        $this->assertSame($wikiIdentifier, $snapshot->wikiIdentifier());
        $this->assertSame($translationSetIdentifier, $snapshot->translationSetIdentifier());
        $this->assertSame($slug, $snapshot->slug());
        $this->assertSame($language, $snapshot->language());
        $this->assertSame($resourceType, $snapshot->resourceType());
        $this->assertSame($basic, $snapshot->basic());
        $this->assertSame($sections, $snapshot->sections());
        $this->assertSame($themeColor, $snapshot->themeColor());
        $this->assertSame($version, $snapshot->version());
        $this->assertSame($editorIdentifier, $snapshot->editorIdentifier());
        $this->assertSame($approverIdentifier, $snapshot->approverIdentifier());
        $this->assertSame($mergerIdentifier, $snapshot->mergerIdentifier());
        $this->assertSame($sourceEditorIdentifier, $snapshot->sourceEditorIdentifier());
        $this->assertSame($mergedAt, $snapshot->mergedAt());
        $this->assertSame($translatedAt, $snapshot->translatedAt());
        $this->assertSame($approvedAt, $snapshot->approvedAt());
        $this->assertSame($createdAt, $snapshot->createdAt());
    }

    /**
     * 正常系: nullable値がnullでインスタンスが生成されること
     */
    public function test__constructWithNullValues(): void
    {
        $snapshot = new WikiSnapshot(
            snapshotIdentifier: new WikiSnapshotIdentifier(StrTestHelper::generateUuid()),
            wikiIdentifier: new WikiIdentifier(StrTestHelper::generateUuid()),
            translationSetIdentifier: new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            slug: new Slug('gr-test-slug'),
            language: Language::KOREAN,
            resourceType: ResourceType::AGENCY,
            basic: new AgencyBasic(
                name: new Name('JYP엔터테인먼트'),
                normalizedName: 'jyp',
                ceo: new CEO('정욱'),
                normalizedCeo: 'jungwook',
                foundedIn: null,
                parentAgencyIdentifier: null,
                status: null,
                logoImageIdentifier: null,
                officialWebsite: null,
                socialLinks: [],
            ),
            sections: new SectionContentCollection(),
            themeColor: null,
            version: new Version(1),
            editorIdentifier: null,
            approverIdentifier: null,
            mergerIdentifier: null,
            sourceEditorIdentifier: null,
            mergedAt: null,
            translatedAt: null,
            approvedAt: null,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertNull($snapshot->themeColor());
        $this->assertNull($snapshot->editorIdentifier());
        $this->assertNull($snapshot->approverIdentifier());
        $this->assertNull($snapshot->mergerIdentifier());
        $this->assertNull($snapshot->sourceEditorIdentifier());
        $this->assertNull($snapshot->mergedAt());
        $this->assertNull($snapshot->translatedAt());
        $this->assertNull($snapshot->approvedAt());
    }
}
