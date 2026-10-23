<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\Service\TranslatedWikiData;
use Source\Wiki\Wiki\Application\Service\TranslationServiceInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki\TranslateWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki\TranslateWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki\TranslateWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki\TranslateWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Factory\DraftWikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ProfileCardListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateWikiTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     */
    public function test__construct(): void
    {
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $this->app()->instance(TranslationServiceInterface::class, $translationService);
        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $this->app()->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);

        $translateWiki = $this->app()->make(TranslateWikiInterface::class);
        $this->assertInstanceOf(TranslateWiki::class, $translateWiki);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     */
    public function testProcess(): void
    {
        $testData = $this->createTranslateWikiTestData();

        $principalIdentifier = $testData->principalIdentifier;
        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $input = new TranslateWikiInput(
            $testData->wikiIdentifier,
            $principalIdentifier,
            ResourceType::TALENT,
        );
        $enPublishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $enPublishedWiki = new Wiki(
            $enPublishedWikiIdentifier,
            $testData->translationSetIdentifier,
            $testData->slug,
            Language::ENGLISH,
            ResourceType::TALENT,
            $this->createTalentBasic('Test Wiki'),
            new SectionContentCollection(),
            null,
            new Version(1),
            null,
            $testData->editorIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($testData->wikiIdentifier)
            ->once()
            ->andReturn($testData->wiki);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifierAndLanguages')
            ->with($testData->translationSetIdentifier, [Language::JAPANESE, Language::ENGLISH])
            ->once()
            ->andReturn([$enPublishedWiki]);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $missingGroupIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $wikiRepository->shouldReceive('findById')
            ->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $missingGroupIdentifier)
            ->once()
            ->andReturn(null);
        $jaTranslatedBasic = $this->createTalentBasic('テスト ウィキ');
        $jaTranslatedSections = new SectionContentCollection();

        $enTranslatedBasic = $this->createTalentBasic('Test Wiki');
        $enTranslatedSections = new SectionContentCollection();

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateWiki')
            ->with($testData->wiki, Language::JAPANESE)
            ->once()
            ->andReturn(new TranslatedWikiData(
                translatedBasic: TalentBasic::fromArray([
                    ...$jaTranslatedBasic->toArray(),
                    'group_identifiers' => [(string) $missingGroupIdentifier],
                ]),
                translatedSections: $jaTranslatedSections,
                translatedTitle: new SeoTitle('翻訳タイトル'),
                translatedMetaDescription: new MetaDescription('翻訳説明文'),
                translatedKeywords: new SeoKeywords(['翻訳キーワード']),
            ));
        $translationService->shouldReceive('translateWiki')
            ->with($testData->wiki, Language::ENGLISH)
            ->once()
            ->andReturn(new TranslatedWikiData(
                translatedBasic: $enTranslatedBasic,
                translatedSections: $enTranslatedSections,
            ));

        $jaDraftWiki = new DraftWiki(
            new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            null,
            $testData->translationSetIdentifier,
            $testData->slug,
            Language::JAPANESE,
            ResourceType::TALENT,
            $jaTranslatedBasic,
            $jaTranslatedSections,
            null,
            ApprovalStatus::Pending,
            $principalIdentifier,
        );

        $enDraftWiki = new DraftWiki(
            new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            null,
            $testData->translationSetIdentifier,
            $testData->slug,
            Language::ENGLISH,
            ResourceType::TALENT,
            $enTranslatedBasic,
            $enTranslatedSections,
            null,
            ApprovalStatus::Pending,
            $principalIdentifier,
        );

        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $draftWikiFactory->shouldReceive('create')
            ->with(
                $principalIdentifier,
                Language::JAPANESE,
                Mockery::on(fn (BasicInterface $basic) => $basic->toArray() === $jaTranslatedBasic->toArray()),
                $testData->slug,
                $testData->translationSetIdentifier,
            )
            ->once()
            ->andReturn($jaDraftWiki);
        $draftWikiFactory->shouldReceive('create')
            ->with(
                $principalIdentifier,
                Language::ENGLISH,
                $enTranslatedBasic,
                $testData->slug,
                $testData->translationSetIdentifier,
            )
            ->once()
            ->andReturn($enDraftWiki);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app()->instance(TranslationServiceInterface::class, $translationService);
        $this->app()->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);

        $translateWiki = $this->app()->make(TranslateWikiInterface::class);
        $output = new TranslateWikiOutput();
        $translateWiki->process($input, $output);
        $result = $output->toArray();

        $this->assertCount(2, $result['draftWikis']);
        self::assertSame('翻訳タイトル', (string) $jaDraftWiki->title());
        self::assertSame('翻訳説明文', (string) $jaDraftWiki->metaDescription());
        self::assertSame(['翻訳キーワード'], $jaDraftWiki->keywords()?->values());
        self::assertNull($enDraftWiki->title());
        $this->assertSame($principalIdentifier, $jaDraftWiki->editorIdentifier());
        $this->assertSame($principalIdentifier, $enDraftWiki->editorIdentifier());
        $this->assertNull($jaDraftWiki->publishedWikiIdentifier());
        $this->assertSame($enPublishedWikiIdentifier, $enDraftWiki->publishedWikiIdentifier());
        foreach ([$jaDraftWiki, $enDraftWiki] as $draftWiki) {
            $this->assertSame((string) $testData->wikiIdentifier, (string) $draftWiki->translationSource()?->wikiIdentifier());
            $this->assertSame($testData->wiki->version()->value(), $draftWiki->translationSource()?->version()->value());
        }
    }

    /**
     * 異常系：指定したIDのWikiが見つからない場合、例外がスローされること.
     */
    public function testWhenWikiNotFound(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateWikiInput(
            $wikiIdentifier,
            $principalIdentifier,
            ResourceType::TALENT,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($wikiIdentifier)
            ->once()
            ->andReturn(null);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(TranslationServiceInterface::class, $translationService);
        $this->app()->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(WikiNotFoundException::class);
        $translateWiki = $this->app()->make(TranslateWikiInterface::class);
        $translateWiki->process($input, new TranslateWikiOutput());
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $testData = $this->createTranslateWikiTestData();

        $input = new TranslateWikiInput(
            $testData->wikiIdentifier,
            $testData->principalIdentifier,
            ResourceType::TALENT,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($testData->wikiIdentifier)
            ->once()
            ->andReturn($testData->wiki);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($testData->principalIdentifier)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(TranslationServiceInterface::class, $translationService);
        $this->app()->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $translateWiki = $this->app()->make(TranslateWikiInterface::class);
        $translateWiki->process($input, new TranslateWikiOutput());
    }

    /**
     * 異常系：認可に失敗した場合、例外がスローされること.
     */
    public function testUnauthorizedRole(): void
    {
        $testData = $this->createTranslateWikiTestData();

        $principal = new Principal(
            $testData->principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $input = new TranslateWikiInput(
            $testData->wikiIdentifier,
            $testData->principalIdentifier,
            ResourceType::TALENT,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($testData->wikiIdentifier)
            ->once()
            ->andReturn($testData->wiki);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($testData->principalIdentifier)
            ->once()
            ->andReturn($principal);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(TranslationServiceInterface::class, $translationService);
        $this->app()->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(DisallowedException::class);
        $translateWiki = $this->app()->make(TranslateWikiInterface::class);
        $translateWiki->process($input, new TranslateWikiOutput());
    }

    /** @return array<string, array{class-string<GroupBasic|SongBasic|TalentBasic>}> */
    public static function basicTypes(): array
    {
        return ['group' => [GroupBasic::class], 'song' => [SongBasic::class], 'talent' => [TalentBasic::class]];
    }

    /** @param class-string<GroupBasic|SongBasic|TalentBasic> $basicClass */
    #[DataProvider('basicTypes')]
    public function testProcessReplacesOnlyRelationsAvailableInTargetLanguage(string $basicClass): void
    {
        $sourceId = new WikiIdentifier(StrTestHelper::generateUuid());
        $targetId = new WikiIdentifier(StrTestHelper::generateUuid());
        $untranslatedId = new WikiIdentifier(StrTestHelper::generateUuid());
        $missingId = new WikiIdentifier(StrTestHelper::generateUuid());
        $sourceSet = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $untranslatedSet = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $source = Mockery::mock(Wiki::class);
        $source->shouldReceive('language')->andReturn(Language::KOREAN);
        $source->shouldReceive('translationSetIdentifier')->andReturn($sourceSet);
        $target = Mockery::mock(Wiki::class);
        $target->shouldReceive('language')->andReturn(Language::JAPANESE);
        $target->shouldReceive('wikiIdentifier')->andReturn($targetId);
        $untranslated = Mockery::mock(Wiki::class);
        $untranslated->shouldReceive('language')->andReturn(Language::KOREAN);
        $untranslated->shouldReceive('translationSetIdentifier')->andReturn($untranslatedSet);
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $sourceId)->andReturn($source);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $untranslatedId)->andReturn($untranslated);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $missingId)->andReturn(null);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifierAndLanguages')->with($sourceSet, [Language::JAPANESE])->andReturn([$target]);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifierAndLanguages')->with($untranslatedSet, [Language::JAPANESE])->andReturn([]);
        $original = $basicClass::fromArray([
            'name' => '翻訳済みの名前',
            'agency_identifier' => (string) $untranslatedId,
            'group_identifiers' => [(string) $untranslatedId, (string) $sourceId, (string) $missingId],
            'talent_identifiers' => [(string) $sourceId, (string) $untranslatedId],
        ]);
        $before = $original->toArray();
        $result = $this->translateThroughProcess($original, $wikiRepository)->basic();
        $expected = $before;
        $expected['agency_identifier'] = null;
        foreach (['group_identifiers', 'talent_identifiers'] as $key) {
            if (array_key_exists($key, $expected)) {
                $expected[$key] = [(string) $targetId];
            }
        }
        $this->assertSame($expected, $result->toArray());
        $this->assertSame($before, $original->toArray());
    }

    public function testProcessKeepsExistingTargetAndRemovesMissingRelations(): void
    {
        $targetId = new WikiIdentifier(StrTestHelper::generateUuid());
        $missingId = new WikiIdentifier(StrTestHelper::generateUuid());
        $target = Mockery::mock(Wiki::class);
        $target->shouldReceive('language')->andReturn(Language::JAPANESE);
        $target->shouldReceive('wikiIdentifier')->andReturn($targetId);
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $targetId)->andReturn($target);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $missingId)->andReturn(null);
        $basic = SongBasic::fromArray([
            'name' => 'Song',
            'agency_identifier' => (string) $targetId,
            'group_identifiers' => [(string) $missingId],
            'talent_identifiers' => [(string) $missingId],
        ]);
        $result = $this->translateThroughProcess($basic, $wikiRepository)->basic()->toArray();
        $this->assertSame((string) $targetId, $result['agency_identifier']);
        $this->assertSame([], $result['group_identifiers']);
        $this->assertSame([], $result['talent_identifiers']);
    }

    public function testProcessResolvesProfileLinksInNestedSections(): void
    {
        $sourceId = new WikiIdentifier(StrTestHelper::generateUuid());
        $targetId = new WikiIdentifier(StrTestHelper::generateUuid());
        $untranslatedId = new WikiIdentifier(StrTestHelper::generateUuid());
        $missingId = new WikiIdentifier(StrTestHelper::generateUuid());
        $sourceSet = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $untranslatedSet = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $source = Mockery::mock(Wiki::class);
        $source->shouldReceive('language')->andReturn(Language::KOREAN);
        $source->shouldReceive('translationSetIdentifier')->andReturn($sourceSet);
        $target = Mockery::mock(Wiki::class);
        $target->shouldReceive('language')->andReturn(Language::JAPANESE);
        $target->shouldReceive('wikiIdentifier')->andReturn($targetId);
        $untranslated = Mockery::mock(Wiki::class);
        $untranslated->shouldReceive('language')->andReturn(Language::KOREAN);
        $untranslated->shouldReceive('translationSetIdentifier')->andReturn($untranslatedSet);
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        foreach ([(string) $sourceId => $source, (string) $targetId => $target, (string) $untranslatedId => $untranslated, (string) $missingId => null] as $identifier => $wiki) {
            $wikiRepository->shouldReceive('findById')
                ->withArgs(fn (WikiIdentifier $id) => (string) $id === $identifier)->andReturn($wiki);
        }
        $wikiRepository->shouldReceive('findByTranslationSetIdentifierAndLanguages')
            ->with($sourceSet, [Language::JAPANESE])->andReturn([$target]);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifierAndLanguages')
            ->with($untranslatedSet, [Language::JAPANESE])->andReturn([]);
        $text = new TextBlock(1, '翻訳済みの本文');
        $profiles = new ProfileCardListBlock(3, [$sourceId, $targetId, $untranslatedId, $missingId], '関連プロフィール');
        $emptyProfiles = new ProfileCardListBlock(2, [$untranslatedId, $missingId]);
        $sections = new SectionContentCollection([
            new Section('翻訳済みの見出し', 5, new SectionContentCollection([
                $text,
                $profiles,
                new Section('子見出し', 4, new SectionContentCollection([
                    new Section('孫見出し', 1, new SectionContentCollection([$profiles, $emptyProfiles]), 3),
                ]), 2),
            ])),
        ]);

        $draft = $this->translateThroughProcess($this->createTalentBasic('テスト'), $wikiRepository, $sections);
        $section = $draft->sections()->sections()[0];
        self::assertSame('翻訳済みの見出し', $section->title());
        self::assertSame(5, $section->displayOrder());
        self::assertSame(1, $section->depth());
        self::assertSame($text, $section->contents()->blocks()[0]);
        $resolved = $section->contents()->blocks()[1];
        self::assertInstanceOf(ProfileCardListBlock::class, $resolved);
        self::assertSame([(string) $targetId], array_map(strval(...), $resolved->wikiIdentifiers()));
        self::assertSame('関連プロフィール', $resolved->title());
        self::assertSame(3, $resolved->displayOrder());
        $child = $section->contents()->sections()[0];
        self::assertSame('子見出し', $child->title());
        self::assertSame(4, $child->displayOrder());
        self::assertSame(2, $child->depth());
        $grandchild = $child->contents()->sections()[0];
        self::assertSame('孫見出し', $grandchild->title());
        self::assertSame(3, $grandchild->depth());
        $nested = $grandchild->contents()->blocks();
        self::assertInstanceOf(ProfileCardListBlock::class, $nested[0]);
        self::assertSame([(string) $targetId], array_map(strval(...), $nested[0]->wikiIdentifiers()));
        self::assertInstanceOf(ProfileCardListBlock::class, $nested[1]);
        self::assertSame([], $nested[1]->wikiIdentifiers());
        self::assertNull($nested[1]->title());
        self::assertSame([$sourceId, $targetId, $untranslatedId, $missingId], $profiles->wikiIdentifiers());
        self::assertSame([$untranslatedId, $missingId], $emptyProfiles->wikiIdentifiers());
    }

    private function translateThroughProcess(
        BasicInterface $basic,
        WikiRepositoryInterface $wikiRepository,
        ?SectionContentCollection $sections = null,
    ): DraftWiki {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::from($basic->getBasicType());
        $wiki = new Wiki(
            $wikiIdentifier,
            $translationSetIdentifier,
            new Slug($resourceType->slugPrefix() . '-test'),
            Language::KOREAN,
            $resourceType,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
            null,
            $principalIdentifier,
        );
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository->shouldReceive('findById')->with($wikiIdentifier)->once()->andReturn($wiki);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifierAndLanguages')
            ->with($translationSetIdentifier, [Language::JAPANESE, Language::ENGLISH])->once()->andReturn([]);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->with($principalIdentifier)->once()->andReturn(new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        ));
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateWiki')->with($wiki, Language::JAPANESE)->once()
            ->andReturn(new TranslatedWikiData($basic, $sections ?? new SectionContentCollection()));
        $englishBasic = $basic::fromArray([
            ...$basic->toArray(),
            'agency_identifier' => null,
            'group_identifiers' => [],
            'talent_identifiers' => [],
        ]);
        $translationService->shouldReceive('translateWiki')->with($wiki, Language::ENGLISH)->once()
            ->andReturn(new TranslatedWikiData($englishBasic, new SectionContentCollection()));
        $saved = [];
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('save')->twice()->andReturnUsing(function (DraftWiki $draft) use (&$saved): void {
            $saved[$draft->language()->value] = $draft;
        });
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(TranslationServiceInterface::class, $translationService);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app()->make(TranslateWikiInterface::class)->process(
            new TranslateWikiInput($wikiIdentifier, $principalIdentifier, $resourceType),
            new TranslateWikiOutput(),
        );
        $this->assertCount(2, $saved);

        return $saved[Language::JAPANESE->value];
    }

    private function createTalentBasic(string $name): TalentBasic
    {
        return TalentBasic::fromArray([
            'type' => 'talent',
            'name' => $name,
            'normalized_name' => 'test',
            'real_name' => '',
            'normalized_real_name' => '',
            'birthday' => null,
            'agency_identifier' => null,
            'group_identifiers' => [],
            'emoji' => '',
            'representative_symbol' => '',
            'position' => '',
            'fandom_name' => '',
        ]);
    }

    private function createTranslateWikiTestData(): TranslateWikiTestData
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('gr-test-wiki');
        $language = Language::KOREAN;

        $basic = $this->createTalentBasic('테스트 위키');
        $sections = new SectionContentCollection();

        $wiki = new Wiki(
            $wikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            ResourceType::TALENT,
            $basic,
            $sections,
            null,
            new Version(1),
            null,
            $editorIdentifier,
        );

        return new TranslateWikiTestData(
            wikiIdentifier: $wikiIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            principalIdentifier: $principalIdentifier,
            editorIdentifier: $editorIdentifier,
            slug: $slug,
            language: $language,
            basic: $basic,
            sections: $sections,
            wiki: $wiki,
        );
    }
}

readonly class TranslateWikiTestData
{
    public function __construct(
        public WikiIdentifier           $wikiIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $principalIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Slug                     $slug,
        public Language                 $language,
        public BasicInterface           $basic,
        public SectionContentCollection $sections,
        public Wiki                     $wiki,
    ) {
    }
}
