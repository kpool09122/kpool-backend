<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftWikiTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        $this->assertSame((string) $data->wikiIdentifier, (string) $draftWiki->wikiIdentifier());
        $this->assertSame((string) $data->publishedWikiIdentifier, (string) $draftWiki->publishedWikiIdentifier());
        $this->assertSame((string) $data->translationSetIdentifier, (string) $draftWiki->translationSetIdentifier());
        $this->assertSame((string) $data->slug, (string) $draftWiki->slug());
        $this->assertSame($data->language->value, $draftWiki->language()->value);
        $this->assertSame($data->resourceType, $draftWiki->resourceType());
        $this->assertSame($data->basic, $draftWiki->basic());
        $this->assertSame($data->sections, $draftWiki->sections());
        $this->assertSame($data->themeColor, $draftWiki->themeColor());
        $this->assertSame($data->status, $draftWiki->status());
        $this->assertSame($data->editorIdentifier, $draftWiki->editorIdentifier());
        $this->assertSame($data->approverIdentifier, $draftWiki->approverIdentifier());
        $this->assertNull($draftWiki->mergerIdentifier());
        $this->assertNull($draftWiki->sourceEditorIdentifier());
        $this->assertNull($draftWiki->mergedAt());
        $this->assertNull($draftWiki->translatedAt());
        $this->assertNull($draftWiki->approvedAt());
    }

    /**
     * 正常系: publishedWikiIdentifierがnullの場合
     *
     * @return void
     */
    public function testConstructWithoutPublishedWikiIdentifier(): void
    {
        $data = $this->createDummyDraftWiki(hasPublishedWiki: false);
        $draftWiki = $data->draftWiki;

        $this->assertNull($draftWiki->publishedWikiIdentifier());
    }

    /**
     * 正常系：setPublishedWikiIdentifierが正しく動作すること.
     *
     * @return void
     */
    public function testSetPublishedWikiIdentifier(): void
    {
        $data = $this->createDummyDraftWiki(hasPublishedWiki: false);
        $draftWiki = $data->draftWiki;

        $this->assertNull($draftWiki->publishedWikiIdentifier());

        $newPublishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $draftWiki->setPublishedWikiIdentifier($newPublishedWikiIdentifier);
        $this->assertSame($newPublishedWikiIdentifier, $draftWiki->publishedWikiIdentifier());
    }

    /**
     * 正常系：setBasicが正しく動作すること.
     *
     * @return void
     */
    public function testSetBasic(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        $this->assertSame($data->basic, $draftWiki->basic());

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
        $draftWiki->setBasic($newBasic);
        $this->assertSame($newBasic, $draftWiki->basic());
        $this->assertNotSame($data->basic, $draftWiki->basic());
    }

    /**
     * 正常系：setSectionsが正しく動作すること.
     *
     * @return void
     */
    public function testSetSections(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        $this->assertSame($data->sections, $draftWiki->sections());

        $newSections = new SectionContentCollection();
        $draftWiki->setSections($newSections);
        $this->assertSame($newSections, $draftWiki->sections());
    }

    /**
     * 正常系：setThemeColorが正しく動作すること.
     *
     * @return void
     */
    public function testSetThemeColor(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        $this->assertSame($data->themeColor, $draftWiki->themeColor());

        // 新しい色を設定
        $newColor = new Color('#00FF00');
        $draftWiki->setThemeColor($newColor);
        $this->assertSame($newColor, $draftWiki->themeColor());
        $this->assertNotSame($data->themeColor, $draftWiki->themeColor());

        // nullを設定
        $draftWiki->setThemeColor(null);
        $this->assertNull($draftWiki->themeColor());
    }

    /**
     * 正常系：setStatusが正しく動作すること.
     *
     * @return void
     */
    public function testSetStatus(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        $this->assertSame(ApprovalStatus::Pending, $draftWiki->status());

        // UnderReviewに変更
        $draftWiki->setStatus(ApprovalStatus::UnderReview);
        $this->assertSame(ApprovalStatus::UnderReview, $draftWiki->status());

        // Approvedに変更
        $draftWiki->setStatus(ApprovalStatus::Approved);
        $this->assertSame(ApprovalStatus::Approved, $draftWiki->status());

        // Rejectedに変更
        $draftWiki->setStatus(ApprovalStatus::Rejected);
        $this->assertSame(ApprovalStatus::Rejected, $draftWiki->status());
    }

    /**
     * 正常系：setApproverIdentifierのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetApproverIdentifier(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        $this->assertSame($data->approverIdentifier, $draftWiki->approverIdentifier());

        // 新しい値を設定
        $newApproverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWiki->setApproverIdentifier($newApproverIdentifier);
        $this->assertSame($newApproverIdentifier, $draftWiki->approverIdentifier());

        // nullを設定
        $draftWiki->setApproverIdentifier(null);
        $this->assertNull($draftWiki->approverIdentifier());
    }

    /**
     * 正常系：setMergerIdentifierのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMergerIdentifier(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        // 初期値はnull
        $this->assertNull($draftWiki->mergerIdentifier());

        // 値を設定
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWiki->setMergerIdentifier($mergerIdentifier);
        $this->assertSame($mergerIdentifier, $draftWiki->mergerIdentifier());

        // nullを設定
        $draftWiki->setMergerIdentifier(null);
        $this->assertNull($draftWiki->mergerIdentifier());
    }

    /**
     * 正常系：setSourceEditorIdentifierのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetSourceEditorIdentifier(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        // 初期値はnull
        $this->assertNull($draftWiki->sourceEditorIdentifier());

        // 値を設定
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWiki->setSourceEditorIdentifier($sourceEditorIdentifier);
        $this->assertSame($sourceEditorIdentifier, $draftWiki->sourceEditorIdentifier());

        // nullを設定
        $draftWiki->setSourceEditorIdentifier(null);
        $this->assertNull($draftWiki->sourceEditorIdentifier());
    }

    /**
     * 正常系：setMergedAtのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMergedAt(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        // 初期値はnull
        $this->assertNull($draftWiki->mergedAt());

        // 値を設定
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');
        $draftWiki->setMergedAt($mergedAt);
        $this->assertSame($mergedAt, $draftWiki->mergedAt());

        // nullを設定
        $draftWiki->setMergedAt(null);
        $this->assertNull($draftWiki->mergedAt());
    }

    /**
     * 正常系：setTranslatedAtのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetTranslatedAt(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        // 初期値はnull
        $this->assertNull($draftWiki->translatedAt());

        // 値を設定
        $translatedAt = new DateTimeImmutable('2026-01-02 12:00:00');
        $draftWiki->setTranslatedAt($translatedAt);
        $this->assertSame($translatedAt, $draftWiki->translatedAt());

        // nullを設定
        $draftWiki->setTranslatedAt(null);
        $this->assertNull($draftWiki->translatedAt());
    }

    /**
     * 正常系：setApprovedAtのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetApprovedAt(): void
    {
        $data = $this->createDummyDraftWiki();
        $draftWiki = $data->draftWiki;

        // 初期値はnull
        $this->assertNull($draftWiki->approvedAt());

        // 値を設定
        $approvedAt = new DateTimeImmutable('2026-01-02 12:00:00');
        $draftWiki->setApprovedAt($approvedAt);
        $this->assertSame($approvedAt, $draftWiki->approvedAt());

        // nullを設定
        $draftWiki->setApprovedAt(null);
        $this->assertNull($draftWiki->approvedAt());
    }

    /**
     * ダミーのDraftWikiを作成するヘルパーメソッド
     *
     * @return DraftWikiTestData
     */
    private function createDummyDraftWiki(
        ?bool $hasPublishedWiki = null,
    ): DraftWikiTestData {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $hasPublishedWiki ??= true;
        $publishedWikiIdentifier = $hasPublishedWiki ? new WikiIdentifier(StrTestHelper::generateUuid()) : null;
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
        $status = ApprovalStatus::Pending;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $draftWiki = new DraftWiki(
            $wikiIdentifier,
            $publishedWikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $basic,
            $sections,
            $themeColor,
            $status,
            $editorIdentifier,
            $approverIdentifier,
        );

        return new DraftWikiTestData(
            wikiIdentifier: $wikiIdentifier,
            publishedWikiIdentifier: $publishedWikiIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            slug: $slug,
            language: $language,
            resourceType: $resourceType,
            basic: $basic,
            sections: $sections,
            themeColor: $themeColor,
            status: $status,
            editorIdentifier: $editorIdentifier,
            approverIdentifier: $approverIdentifier,
            draftWiki: $draftWiki,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class DraftWikiTestData
{
    public function __construct(
        public DraftWikiIdentifier       $wikiIdentifier,
        public ?WikiIdentifier           $publishedWikiIdentifier,
        public TranslationSetIdentifier  $translationSetIdentifier,
        public Slug                      $slug,
        public Language                  $language,
        public ResourceType              $resourceType,
        public BasicInterface            $basic,
        public SectionContentCollection  $sections,
        public ?Color                    $themeColor,
        public ApprovalStatus            $status,
        public ?PrincipalIdentifier      $editorIdentifier,
        public ?PrincipalIdentifier      $approverIdentifier,
        public DraftWiki                 $draftWiki,
    ) {
    }
}
