<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $data = $this->createDummyWiki(isOfficial: false);
        $wiki = $data->wiki;

        $this->assertSame((string) $data->wikiIdentifier, (string) $wiki->wikiIdentifier());
        $this->assertSame((string) $data->translationSetIdentifier, (string) $wiki->translationSetIdentifier());
        $this->assertSame((string) $data->slug, (string) $wiki->slug());
        $this->assertSame($data->language->value, $wiki->language()->value);
        $this->assertSame($data->resourceType, $wiki->resourceType());
        $this->assertSame($data->basic, $wiki->basic());
        $this->assertSame($data->sections, $wiki->sections());
        $this->assertSame($data->themeColor, $wiki->themeColor());
        $this->assertSame($data->version->value(), $wiki->version()->value());
        $this->assertNull($wiki->ownerAccountIdentifier());
        $this->assertFalse($wiki->isOfficial());
        $this->assertSame($data->editorIdentifier, $wiki->editorIdentifier());
        $this->assertSame($data->approverIdentifier, $wiki->approverIdentifier());
        $this->assertNull($wiki->mergerIdentifier());
        $this->assertNull($wiki->sourceEditorIdentifier());
        $this->assertNull($wiki->mergedAt());
        $this->assertNull($wiki->translatedAt());
        $this->assertNull($wiki->approvedAt());

        $data = $this->createDummyWiki(isOfficial: true);
        $wiki = $data->wiki;
        $this->assertTrue($wiki->isOfficial());
        $this->assertNotNull($wiki->ownerAccountIdentifier());
    }

    /**
     * 正常系：setBasicが正しく動作すること.
     *
     * @return void
     */
    public function testSetBasic(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        $this->assertSame($data->basic, $wiki->basic());

        $newBasic = AgencyBasic::fromArray([
            'name' => 'HYBE',
            'normalized_name' => 'hybe',
            'ceo' => 'パン・シヒョク',
            'normalized_ceo' => 'ぱんしひょく',
            'founded_in' => '2005-02-01',
            'parent_agency_identifier' => null,
            'status' => null,
            'logo_image_identifier' => null,
            'official_website' => null,
            'social_links' => [],
        ]);
        $wiki->setBasic($newBasic);
        $this->assertSame($newBasic, $wiki->basic());
        $this->assertNotSame($data->basic, $wiki->basic());
    }

    /**
     * 正常系：setSectionsが正しく動作すること.
     *
     * @return void
     */
    public function testSetSections(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        $this->assertSame($data->sections, $wiki->sections());

        $newSections = new SectionContentCollection();
        $wiki->setSections($newSections);
        $this->assertSame($newSections, $wiki->sections());
    }

    /**
     * 正常系：setThemeColorが正しく動作すること.
     *
     * @return void
     */
    public function testSetThemeColor(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        $this->assertSame($data->themeColor, $wiki->themeColor());

        // 新しい色を設定
        $newColor = new Color('#00FF00');
        $wiki->setThemeColor($newColor);
        $this->assertSame($newColor, $wiki->themeColor());
        $this->assertNotSame($data->themeColor, $wiki->themeColor());

        // nullを設定
        $wiki->setThemeColor(null);
        $this->assertNull($wiki->themeColor());
    }

    /**
     * 正常系：updateVersionが正しく動作すること.
     *
     * @return void
     */
    public function testUpdateVersion(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        $this->assertSame($data->version->value(), $wiki->version()->value());

        $wiki->updateVersion();

        $this->assertNotSame($data->version->value(), $wiki->version()->value());
        $this->assertSame($data->version->value() + 1, $wiki->version()->value());
    }

    /**
     * 正常系：hasSameVersionが正しく動作すること.
     *
     * @return void
     */
    public function testHasSameVersion(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        // 同じバージョン
        $sameVersion = new Version(1);
        $this->assertTrue($wiki->hasSameVersion($sameVersion));

        // 異なるバージョン
        $differentVersion = new Version(2);
        $this->assertFalse($wiki->hasSameVersion($differentVersion));
    }

    /**
     * 正常系：isVersionGreaterThanが正しく動作すること.
     *
     * @return void
     */
    public function testIsVersionGreaterThan(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $basic = AgencyBasic::fromArray([
            'name' => 'JYP엔터테인먼트',
            'normalized_name' => 'jypㅇㅌㅌㅇㅁㅌ',
            'ceo' => 'J.Y. Park',
            'normalized_ceo' => 'j.y.park',
            'founded_in' => '1997-04-25',
            'parent_agency_identifier' => null,
            'status' => null,
            'logo_image_identifier' => null,
            'official_website' => null,
            'social_links' => [],
        ]);
        $wiki = new Wiki(
            $wikiIdentifier,
            $translationSetIdentifier,
            new Slug('ag-jyp-entertainment'),
            Language::KOREAN,
            ResourceType::AGENCY,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(5),
        );

        // 現在のバージョンより小さいバージョン
        $smallerVersion = new Version(3);
        $this->assertTrue($wiki->isVersionGreaterThan($smallerVersion));

        // 現在のバージョンと同じバージョン
        $sameVersion = new Version(5);
        $this->assertFalse($wiki->isVersionGreaterThan($sameVersion));

        // 現在のバージョンより大きいバージョン
        $largerVersion = new Version(7);
        $this->assertFalse($wiki->isVersionGreaterThan($largerVersion));
    }

    /**
     * 正常系：setEditorIdentifierのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetEditorIdentifier(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        $this->assertSame($data->editorIdentifier, $wiki->editorIdentifier());

        // 新しい値を設定
        $newEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wiki->setEditorIdentifier($newEditorIdentifier);
        $this->assertSame($newEditorIdentifier, $wiki->editorIdentifier());

        // nullを設定
        $wiki->setEditorIdentifier(null);
        $this->assertNull($wiki->editorIdentifier());
    }

    /**
     * 正常系：setApproverIdentifierのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetApproverIdentifier(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        $this->assertSame($data->approverIdentifier, $wiki->approverIdentifier());

        // 新しい値を設定
        $newApproverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wiki->setApproverIdentifier($newApproverIdentifier);
        $this->assertSame($newApproverIdentifier, $wiki->approverIdentifier());

        // nullを設定
        $wiki->setApproverIdentifier(null);
        $this->assertNull($wiki->approverIdentifier());
    }

    /**
     * 正常系：setMergerIdentifierのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMergerIdentifier(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        // 初期値はnull
        $this->assertNull($wiki->mergerIdentifier());

        // 値を設定
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wiki->setMergerIdentifier($mergerIdentifier);
        $this->assertSame($mergerIdentifier, $wiki->mergerIdentifier());

        // nullを設定
        $wiki->setMergerIdentifier(null);
        $this->assertNull($wiki->mergerIdentifier());
    }

    /**
     * 正常系：setSourceEditorIdentifierのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetSourceEditorIdentifier(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        // 初期値はnull
        $this->assertNull($wiki->sourceEditorIdentifier());

        // 値を設定
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wiki->setSourceEditorIdentifier($sourceEditorIdentifier);
        $this->assertSame($sourceEditorIdentifier, $wiki->sourceEditorIdentifier());

        // nullを設定
        $wiki->setSourceEditorIdentifier(null);
        $this->assertNull($wiki->sourceEditorIdentifier());
    }

    /**
     * 正常系：setMergedAtのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMergedAt(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        // 初期値はnull
        $this->assertNull($wiki->mergedAt());

        // 値を設定
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');
        $wiki->setMergedAt($mergedAt);
        $this->assertSame($mergedAt, $wiki->mergedAt());

        // nullを設定
        $wiki->setMergedAt(null);
        $this->assertNull($wiki->mergedAt());
    }

    /**
     * 正常系：setTranslatedAtのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetTranslatedAt(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        // 初期値はnull
        $this->assertNull($wiki->translatedAt());

        // 値を設定
        $translatedAt = new DateTimeImmutable('2026-01-02 12:00:00');
        $wiki->setTranslatedAt($translatedAt);
        $this->assertSame($translatedAt, $wiki->translatedAt());

        // nullを設定
        $wiki->setTranslatedAt(null);
        $this->assertNull($wiki->translatedAt());
    }

    /**
     * 正常系：setApprovedAtのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetApprovedAt(): void
    {
        $data = $this->createDummyWiki();
        $wiki = $data->wiki;

        // 初期値はnull
        $this->assertNull($wiki->approvedAt());

        // 値を設定
        $approvedAt = new DateTimeImmutable('2026-01-02 12:00:00');
        $wiki->setApprovedAt($approvedAt);
        $this->assertSame($approvedAt, $wiki->approvedAt());

        // nullを設定
        $wiki->setApprovedAt(null);
        $this->assertNull($wiki->approvedAt());
    }

    /**
     * 正常系：非公式のWikiにmarkOfficialを呼ぶと公式になること.
     *
     * @return void
     */
    public function testMarkOfficial(): void
    {
        $data = $this->createDummyWiki(isOfficial: false);
        $wiki = $data->wiki;

        $this->assertFalse($wiki->isOfficial());
        $this->assertNull($wiki->ownerAccountIdentifier());

        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki->markOfficial($ownerAccountIdentifier);

        $this->assertTrue($wiki->isOfficial());
        $this->assertSame($ownerAccountIdentifier, $wiki->ownerAccountIdentifier());
    }

    /**
     * 正常系：既に公式のWikiにmarkOfficialを呼んでもownerAccountIdentifierが変更されないこと.
     *
     * @return void
     */
    public function testMarkOfficialWhenAlreadyOfficial(): void
    {
        $data = $this->createDummyWiki(isOfficial: true);
        $wiki = $data->wiki;

        $this->assertTrue($wiki->isOfficial());
        $originalOwner = $wiki->ownerAccountIdentifier();

        $newOwnerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki->markOfficial($newOwnerAccountIdentifier);

        $this->assertTrue($wiki->isOfficial());
        $this->assertSame($originalOwner, $wiki->ownerAccountIdentifier());
    }

    /**
     * ダミーのWikiを作成するヘルパーメソッド
     *
     * @return WikiTestData
     */
    private function createDummyWiki(
        ?bool $isOfficial = null,
    ): WikiTestData {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('ag-jyp-entertainment');
        $language = Language::KOREAN;
        $resourceType = ResourceType::AGENCY;
        $basic = AgencyBasic::fromArray([
            'name' => 'JYP엔터테인먼트',
            'normalized_name' => 'jypㅇㅌㅌㅇㅁㅌ',
            'ceo' => 'J.Y. Park',
            'normalized_ceo' => 'j.y.park',
            'founded_in' => '1997-04-25',
            'parent_agency_identifier' => null,
            'status' => null,
            'logo_image_identifier' => null,
            'official_website' => null,
            'social_links' => [],
        ]);
        $sections = new SectionContentCollection();
        $themeColor = new Color('#FF5733');
        $version = new Version(1);
        $isOfficial ??= false;
        $ownerAccountIdentifier = $isOfficial ? new AccountIdentifier(StrTestHelper::generateUuid()) : null;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $wiki = new Wiki(
            $wikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $basic,
            $sections,
            $themeColor,
            $version,
            $ownerAccountIdentifier,
            $editorIdentifier,
            $approverIdentifier,
        );

        return new WikiTestData(
            wikiIdentifier: $wikiIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            slug: $slug,
            language: $language,
            resourceType: $resourceType,
            basic: $basic,
            sections: $sections,
            themeColor: $themeColor,
            version: $version,
            ownerAccountIdentifier: $ownerAccountIdentifier,
            editorIdentifier: $editorIdentifier,
            approverIdentifier: $approverIdentifier,
            isOfficial: $isOfficial,
            wiki: $wiki,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class WikiTestData
{
    public function __construct(
        public WikiIdentifier            $wikiIdentifier,
        public TranslationSetIdentifier  $translationSetIdentifier,
        public Slug                      $slug,
        public Language                  $language,
        public ResourceType              $resourceType,
        public BasicInterface            $basic,
        public SectionContentCollection  $sections,
        public ?Color                    $themeColor,
        public Version                   $version,
        public ?AccountIdentifier        $ownerAccountIdentifier,
        public ?PrincipalIdentifier      $editorIdentifier,
        public ?PrincipalIdentifier      $approverIdentifier,
        public bool                      $isOfficial,
        public Wiki                      $wiki,
    ) {
    }
}
