<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\WikiService;
use Source\Wiki\Wiki\Domain\Service\WikiServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\HexColor;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\TranslationSource;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiServiceTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);
        $this->assertInstanceOf(WikiService::class, $wikiService);
    }

    /**
     * 正常系: 公開Wikiが存在しない場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHasConsistentVersionsWhenEmpty(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([]);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->hasConsistentVersions($translationSetIdentifier);

        $this->assertTrue($result);
    }

    /**
     * 正常系: 公開Wikiが1件のみの場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHasConsistentVersionsWhenSingle(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $wiki = $this->createDummyWiki($translationSetIdentifier, Language::KOREAN, new Version(3));

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$wiki]);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->hasConsistentVersions($translationSetIdentifier);

        $this->assertTrue($result);
    }

    /**
     * 正常系: すべての公開Wikiのバージョンが同じ場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHasConsistentVersionsWhenAllSame(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $koreanWiki = $this->createDummyWiki($translationSetIdentifier, Language::KOREAN, new Version(3));
        $japaneseWiki = $this->createDummyWiki($translationSetIdentifier, Language::JAPANESE, new Version(3));
        $englishWiki = $this->createDummyWiki($translationSetIdentifier, Language::ENGLISH, new Version(3));

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$koreanWiki, $japaneseWiki, $englishWiki]);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->hasConsistentVersions($translationSetIdentifier);

        $this->assertTrue($result);
    }

    /**
     * 正常系: 公開Wikiのバージョンが異なる場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHasConsistentVersionsWhenDifferent(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $koreanWiki = $this->createDummyWiki($translationSetIdentifier, Language::KOREAN, new Version(3));
        $japaneseWiki = $this->createDummyWiki($translationSetIdentifier, Language::JAPANESE, new Version(2));

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$koreanWiki, $japaneseWiki]);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->hasConsistentVersions($translationSetIdentifier);

        $this->assertFalse($result);
    }

    /**
     * 正常系: 3件中1件だけバージョンが異なる場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHasConsistentVersionsWhenOneOutOfSync(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $koreanWiki = $this->createDummyWiki($translationSetIdentifier, Language::KOREAN, new Version(3));
        $japaneseWiki = $this->createDummyWiki($translationSetIdentifier, Language::JAPANESE, new Version(3));
        $englishWiki = $this->createDummyWiki($translationSetIdentifier, Language::ENGLISH, new Version(2));

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$koreanWiki, $japaneseWiki, $englishWiki]);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->hasConsistentVersions($translationSetIdentifier);

        $this->assertFalse($result);
    }

    /**
     * 正常系: Approved状態のDraftWikiが存在する場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedDraftWikiWhenApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeWikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());

        // 承認済みのDraftWiki (韓国語版)
        $approvedDraftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            ApprovalStatus::Approved,
        );

        // 除外対象のDraftWiki (日本語版)
        $excludeDraftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::JAPANESE,
            ApprovalStatus::Pending,
            $excludeWikiIdentifier,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedDraftWiki, $excludeDraftWiki]);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->existsConflictingApprovedDraftWiki($excludeDraftWiki);

        $this->assertTrue($result);
    }

    /**
     * 正常系: Approved状態のDraftWikiが存在しない場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedDraftWikiWhenNoApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeWikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());

        // Pending状態のDraftWiki (韓国語版)
        $pendingDraftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            ApprovalStatus::Pending,
        );

        // 除外対象のDraftWiki (日本語版)
        $excludeDraftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::JAPANESE,
            ApprovalStatus::Pending,
            $excludeWikiIdentifier,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$pendingDraftWiki, $excludeDraftWiki]);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->existsConflictingApprovedDraftWiki($excludeDraftWiki);

        $this->assertFalse($result);
    }

    /**
     * 正常系: 自分自身がApprovedでも除外されるのでfalseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedDraftWikiWhenOnlySelfIsApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeWikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());

        // 自分自身 (Approved状態だが除外される)
        $selfDraftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::JAPANESE,
            ApprovalStatus::Approved,
            $excludeWikiIdentifier,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$selfDraftWiki]);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->existsConflictingApprovedDraftWiki($selfDraftWiki);

        $this->assertFalse($result);
    }

    /**
     * 正常系: DraftWikiが存在しない場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedDraftWikiWhenNoDrafts(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeWikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([]);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->existsConflictingApprovedDraftWiki($this->createDummyDraftWiki($translationSetIdentifier, Language::JAPANESE, ApprovalStatus::UnderReview, $excludeWikiIdentifier));

        $this->assertFalse($result);
    }

    /**
     * 正常系: 複数のApproved状態のDraftWikiが存在する場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedDraftWikiWhenMultipleApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeWikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());

        // 韓国語版 (Approved)
        $koreanDraftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            ApprovalStatus::Approved,
        );

        // 英語版 (Approved)
        $englishDraftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::ENGLISH,
            ApprovalStatus::Approved,
        );

        // 日本語版 (Pending, 除外対象)
        $japaneseDraftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::JAPANESE,
            ApprovalStatus::Pending,
            $excludeWikiIdentifier,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$koreanDraftWiki, $englishDraftWiki, $japaneseDraftWiki]);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $result = $wikiService->existsConflictingApprovedDraftWiki($japaneseDraftWiki);

        $this->assertTrue($result);
    }

    /** @return array<string, array{bool, bool, bool, bool}> */
    public static function translationSourceConflicts(): array
    {
        return [
            'same source and version, another language' => [true, true, true, false],
            'different source Wiki' => [false, true, true, true],
            'different source version' => [true, false, true, true],
            'same language' => [true, true, false, true],
        ];
    }

    #[DataProvider('translationSourceConflicts')]
    public function testApprovedDraftConflictRequiresTheSameTranslationSource(
        bool $sameWiki,
        bool $sameVersion,
        bool $differentLanguage,
        bool $expectedConflict,
    ): void {
        $setId = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $sourceId = new WikiIdentifier(StrTestHelper::generateUuid());
        $draft = $this->createDummyDraftWiki($setId, Language::JAPANESE, ApprovalStatus::UnderReview);
        $draft->setTranslationSource(new TranslationSource($sourceId, new Version(2)));
        $approved = $this->createDummyDraftWiki(
            $setId,
            $differentLanguage ? Language::ENGLISH : Language::JAPANESE,
            ApprovalStatus::Approved,
        );
        $approved->setTranslationSource(new TranslationSource(
            $sameWiki ? $sourceId : new WikiIdentifier(StrTestHelper::generateUuid()),
            new Version($sameVersion ? 2 : 1),
        ));
        // 翻訳を実行した時刻が違っても、翻訳元が同じなら同じ内容の版として扱う。
        $draft->setTranslatedAt(new DateTimeImmutable('2026-10-18 00:00:00'));
        $approved->setTranslatedAt(new DateTimeImmutable('2026-10-17 00:00:00'));
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByTranslationSetIdentifier')->once()->with($setId)->andReturn([$draft, $approved]);
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $service = $this->app()->make(WikiServiceInterface::class);

        $this->assertSame($expectedConflict, $service->existsConflictingApprovedDraftWiki($draft));
    }

    public function testSameSourceDraftDoesNotHideAnotherConflictingApprovedDraft(): void
    {
        $setId = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $source = new TranslationSource(new WikiIdentifier(StrTestHelper::generateUuid()), new Version(2));
        $draft = $this->createDummyDraftWiki($setId, Language::JAPANESE, ApprovalStatus::UnderReview);
        $draft->setTranslationSource($source);
        $sameSourceDraft = $this->createDummyDraftWiki($setId, Language::ENGLISH, ApprovalStatus::Approved);
        $sameSourceDraft->setTranslationSource($source);
        $unknownSourceDraft = $this->createDummyDraftWiki($setId, Language::KOREAN, ApprovalStatus::Approved);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByTranslationSetIdentifier')->once()->with($setId)->andReturn([$sameSourceDraft, $unknownSourceDraft]);
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $service = $this->app()->make(WikiServiceInterface::class);

        $this->assertTrue($service->existsConflictingApprovedDraftWiki($draft));
    }

    public function testResolvePublishVersionAllowsTranslatedDraftToCatchUpExistingLanguage(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $koreanWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $koreanWiki = $this->createDummyWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            new Version(1),
            $koreanWikiIdentifier,
        );
        $japaneseWiki = $this->createDummyWiki($translationSetIdentifier, Language::JAPANESE, new Version(1));
        $englishWiki = $this->createDummyWiki($translationSetIdentifier, Language::ENGLISH, new Version(2));
        $draftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            ApprovalStatus::UnderReview,
            publishedWikiIdentifier: $koreanWikiIdentifier,
            translatedAt: new DateTimeImmutable('2024-01-01 00:00:00'),
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$koreanWiki, $japaneseWiki, $englishWiki]);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $this->assertTrue($wikiService->canApproveDraftWiki($draftWiki));
        $this->assertSame(2, $wikiService->resolvePublishVersion($draftWiki)?->value());
    }

    /** @return array<string, array{int, int, bool, ?int}> */
    public static function normalDraftWithInconsistentVersions(): array
    {
        return [
            '遅れている言語は最新版に追随する' => [1, 2, true, 2],
            '公開先IDがなくても言語から追随先を判定する' => [1, 2, false, 2],
            '先行言語をさらに進めない' => [2, 1, true, null],
            '公開先IDがなくても先行言語をさらに進めない' => [2, 1, false, null],
        ];
    }

    #[DataProvider('normalDraftWithInconsistentVersions')]
    public function testResolvePublishVersionForNormalDraftWhenVersionsAreInconsistent(
        int $targetVersion,
        int $otherVersion,
        bool $hasPublishedWikiIdentifier,
        ?int $expectedVersion,
    ): void {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $koreanWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $koreanWiki = $this->createDummyWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            new Version($targetVersion),
            $koreanWikiIdentifier,
        );
        $englishWiki = $this->createDummyWiki($translationSetIdentifier, Language::ENGLISH, new Version($otherVersion));
        $draftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            ApprovalStatus::UnderReview,
            publishedWikiIdentifier: $hasPublishedWikiIdentifier ? $koreanWikiIdentifier : null,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->times($hasPublishedWikiIdentifier ? 1 : 2)
            ->with($translationSetIdentifier)
            ->andReturn([$koreanWiki, $englishWiki]);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $this->assertSame($hasPublishedWikiIdentifier || $expectedVersion !== null, $wikiService->canApproveDraftWiki($draftWiki));
        $this->assertSame($expectedVersion, $wikiService->resolvePublishVersion($draftWiki)?->value());
    }

    public function testResolvePublishVersionRejectsTranslatedDraftForLatestLanguage(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $englishWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $japaneseWiki = $this->createDummyWiki($translationSetIdentifier, Language::JAPANESE, new Version(2));
        $englishWiki = $this->createDummyWiki(
            $translationSetIdentifier,
            Language::ENGLISH,
            new Version(2),
            $englishWikiIdentifier,
        );
        $draftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::ENGLISH,
            ApprovalStatus::UnderReview,
            publishedWikiIdentifier: $englishWikiIdentifier,
            translatedAt: new DateTimeImmutable('2024-01-01 00:00:00'),
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$japaneseWiki, $englishWiki]);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $this->assertTrue($wikiService->canApproveDraftWiki($draftWiki));
        $this->assertNull($wikiService->resolvePublishVersion($draftWiki));
    }

    /** @return array<string, array{bool, int, ?int}> */
    public static function newLanguageDraftVersions(): array
    {
        return [
            '版が揃っている翻訳下書き' => [true, 2, 2],
            '版が揃っていない翻訳下書き' => [true, 1, 2],
            '版が揃っている通常下書き' => [false, 2, 2],
            '版が揃っていない通常下書き' => [false, 1, null],
        ];
    }

    #[DataProvider('newLanguageDraftVersions')]
    public function testResolvePublishVersionForNewLanguage(
        bool $isTranslated,
        int $otherVersion,
        ?int $expectedVersion,
    ): void {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $japaneseWiki = $this->createDummyWiki($translationSetIdentifier, Language::JAPANESE, new Version(2));
        $englishWiki = $this->createDummyWiki($translationSetIdentifier, Language::ENGLISH, new Version($otherVersion));
        $draftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            ApprovalStatus::UnderReview,
            translatedAt: $isTranslated ? new DateTimeImmutable('2024-01-01 00:00:00') : null,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$japaneseWiki, $englishWiki]);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $this->assertSame($expectedVersion, $wikiService->resolvePublishVersion($draftWiki)?->value());
    }

    public function testResolvePublishVersionAllowsNormalDraftWhenVersionsAreConsistent(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $koreanWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $koreanWiki = $this->createDummyWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            new Version(2),
            $koreanWikiIdentifier,
        );
        $englishWiki = $this->createDummyWiki($translationSetIdentifier, Language::ENGLISH, new Version(2));
        $draftWiki = $this->createDummyDraftWiki(
            $translationSetIdentifier,
            Language::KOREAN,
            ApprovalStatus::UnderReview,
            publishedWikiIdentifier: $koreanWikiIdentifier,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$koreanWiki, $englishWiki]);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app()->make(WikiServiceInterface::class);

        $this->assertSame(3, $wikiService->resolvePublishVersion($draftWiki)?->value());
    }

    /**
     * ダミーの公開Wikiを作成するヘルパーメソッド
     *
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param Version $version
     * @return Wiki
     */
    private function createDummyWiki(
        TranslationSetIdentifier $translationSetIdentifier,
        Language $language,
        Version $version,
        ?WikiIdentifier $wikiIdentifier = null,
    ): Wiki {
        return new Wiki(
            $wikiIdentifier ?? new WikiIdentifier(StrTestHelper::generateUuid()),
            $translationSetIdentifier,
            new Slug('gr-twice'),
            $language,
            ResourceType::GROUP,
            new GroupBasic(
                name: new Name('TWICE'),
                normalizedName: 'twice',
                agencyIdentifier: null,
                groupType: null,
                status: null,
                generation: null,
                debutDate: null,
                disbandDate: null,
                fandomName: new FandomName('ONCE'),
                officialColors: [],
                emoji: new Emoji(''),
                representativeSymbol: new RepresentativeSymbol(''),
            ),
            new SectionContentCollection(),
            new HexColor('#ff5733'),
            $version,
        );
    }

    /**
     * ダミーのDraftWikiを作成するヘルパーメソッド
     *
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param ApprovalStatus $status
     * @param DraftWikiIdentifier|null $wikiIdentifier
     * @return DraftWiki
     */
    private function createDummyDraftWiki(
        TranslationSetIdentifier $translationSetIdentifier,
        Language $language,
        ApprovalStatus $status,
        ?DraftWikiIdentifier $wikiIdentifier = null,
        ?WikiIdentifier $publishedWikiIdentifier = null,
        ?DateTimeImmutable $translatedAt = null,
    ): DraftWiki {
        return new DraftWiki(
            $wikiIdentifier ?? new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            $publishedWikiIdentifier,
            $translationSetIdentifier,
            new Slug('gr-twice'),
            $language,
            ResourceType::GROUP,
            new GroupBasic(
                name: new Name('TWICE'),
                normalizedName: 'twice',
                agencyIdentifier: null,
                groupType: null,
                status: null,
                generation: null,
                debutDate: null,
                disbandDate: null,
                fandomName: new FandomName('ONCE'),
                officialColors: [],
                emoji: new Emoji(''),
                representativeSymbol: new RepresentativeSymbol(''),
            ),
            new SectionContentCollection(),
            new HexColor('#ff5733'),
            $status,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            translatedAt: $translatedAt,
        );
    }
}
