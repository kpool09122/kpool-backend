<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
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
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
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
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);
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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

        $result = $wikiService->existsApprovedDraftWiki(
            $translationSetIdentifier,
            $excludeWikiIdentifier,
        );

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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

        $result = $wikiService->existsApprovedDraftWiki(
            $translationSetIdentifier,
            $excludeWikiIdentifier,
        );

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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

        $result = $wikiService->existsApprovedDraftWiki(
            $translationSetIdentifier,
            $excludeWikiIdentifier,
        );

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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

        $result = $wikiService->existsApprovedDraftWiki(
            $translationSetIdentifier,
            $excludeWikiIdentifier,
        );

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

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = $this->app->make(WikiServiceInterface::class);

        $result = $wikiService->existsApprovedDraftWiki(
            $translationSetIdentifier,
            $excludeWikiIdentifier,
        );

        $this->assertTrue($result);
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
    ): Wiki {
        return new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            $translationSetIdentifier,
            new Slug('twice'),
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
                mainImageIdentifier: null,
            ),
            new SectionContentCollection(),
            new Color('#FF5733'),
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
    ): DraftWiki {
        return new DraftWiki(
            $wikiIdentifier ?? new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            null,
            $translationSetIdentifier,
            new Slug('twice'),
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
                mainImageIdentifier: null,
            ),
            new SectionContentCollection(),
            new Color('#FF5733'),
            $status,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
        );
    }
}
