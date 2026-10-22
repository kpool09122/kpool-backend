<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\Service\TranslationServiceInterface;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedProvider;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageGalleryBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ProfileCardListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\QuoteBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableCell;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Infrastructure\Service\TranslationService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app()->make(TranslationServiceInterface::class);

        $this->assertInstanceOf(TranslationService::class, $service);
    }

    /**
     * 正常系：TalentタイプのWikiのBasic情報が正しく翻訳されること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateTalentWikiBasic(): void
    {
        $wiki = $this->createTalentWiki();
        $targetLanguage = Language::JAPANESE;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'チェヨン',       // name
            'ソン・チェヨン', // real_name
        ]);

        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, $targetLanguage);

        $translatedBasic = $result->translatedBasic();
        $this->assertSame('チェヨン', (string) $translatedBasic->name());
        $this->assertInstanceOf(TalentBasic::class, $translatedBasic);
        /** @var TalentBasic $translatedBasic */
        $this->assertSame('ソン・チェヨン', (string) $translatedBasic->realName());
    }

    /**
     * @param list<string> $basicTranslations
     */
    #[TestWith(['호랑이', '메인 래퍼', ['虎', 'メインラッパー'], '虎', 'メインラッパー'])]
    #[TestWith(['', '메인 래퍼', ['メインラッパー'], '', 'メインラッパー'])]
    #[TestWith(['호랑이', '', ['虎'], '虎', ''])]
    public function testTranslatesTalentSymbolAndPositionWithoutShiftingSectionTexts(
        string $representativeSymbol,
        string $position,
        array $basicTranslations,
        string $expectedSymbol,
        string $expectedPosition,
    ): void {
        $sections = new SectionContentCollection([
            new Section('개요', 0, new SectionContentCollection([new TextBlock(0, '소개')])),
        ]);
        $wiki = $this->createTalentWiki($sections, $representativeSymbol, $position);
        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'チェヨン', 'ソン・チェヨン', ...$basicTranslations, '概要', '紹介文',
        ]);
        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, Language::JAPANESE);

        $basic = $result->translatedBasic();
        self::assertInstanceOf(TalentBasic::class, $basic);
        self::assertSame($expectedSymbol, $basic->representativeSymbol()->value());
        self::assertSame($expectedPosition, $basic->position()->value());
        $section = $result->translatedSections()->all()[0];
        self::assertInstanceOf(Section::class, $section);
        self::assertSame('概要', $section->title());
        $text = $section->contents()->all()[0];
        self::assertInstanceOf(TextBlock::class, $text);
        self::assertSame('紹介文', $text->content());
    }

    public function testSendsTalentSymbolAndPositionToTranslationApi(): void
    {
        $wiki = $this->createTalentWiki(representativeSymbol: '호랑이', position: '메인 래퍼');
        $googleTranslateClient = Mockery::mock(GoogleTranslateClient::class);
        $googleTranslateClient->shouldReceive('translateTexts')->once()
            ->withArgs(static fn (TranslateTextsRequest $request): bool =>
                $request->texts() === ['채영', '손채영', '호랑이', '메인 래퍼']
                && $request->targetLanguage() === 'ja')
            ->andReturn(new TranslateTextsResponse(['チェヨン', 'ソン・チェヨン', '虎', 'メインラッパー']));
        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $this->app()->make(TranslationServiceInterface::class)->translateWiki($wiki, Language::JAPANESE);
    }

    public function testPreservesTalentSymbolAndPositionWhenTranslationsAreMissing(): void
    {
        $wiki = $this->createTalentWiki(representativeSymbol: '호랑이', position: '메인 래퍼');
        $this->app()->instance(GoogleTranslateClient::class, $this->createGoogleTranslateClientMock([]));

        $result = $this->app()->make(TranslationServiceInterface::class)->translateWiki($wiki, Language::JAPANESE);
        $basic = $result->translatedBasic();
        self::assertInstanceOf(TalentBasic::class, $basic);
        self::assertSame('호랑이', $basic->representativeSymbol()->value());
        self::assertSame('메인 래퍼', $basic->position()->value());
    }

    /**
     * 正常系：AgencyタイプのWikiのBasic情報が正しく翻訳されること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateAgencyWikiBasic(): void
    {
        $wiki = $this->createAgencyWiki();
        $targetLanguage = Language::JAPANESE;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'JYPエンターテインメント', // name
            'パク・ジニョン',           // ceo
        ]);

        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, $targetLanguage);

        $translatedBasic = $result->translatedBasic();
        $this->assertSame('JYPエンターテインメント', (string) $translatedBasic->name());
        $this->assertInstanceOf(AgencyBasic::class, $translatedBasic);
        /** @var AgencyBasic $translatedBasic */
        $this->assertSame('パク・ジニョン', (string) $translatedBasic->ceo());
    }

    /**
     * 正常系：GroupタイプのWikiのBasic情報が正しく翻訳されること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateGroupWikiBasic(): void
    {
        $wiki = $this->createGroupWiki();
        $targetLanguage = Language::JAPANESE;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'トゥワイス', // name
        ]);

        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, $targetLanguage);

        $translatedBasic = $result->translatedBasic();
        $this->assertSame('トゥワイス', (string) $translatedBasic->name());
        $this->assertInstanceOf(GroupBasic::class, $translatedBasic);
    }

    /**
     * 正常系：SongタイプのWikiのBasic情報が正しく翻訳されること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateSongWikiBasic(): void
    {
        $wiki = $this->createSongWiki();
        $targetLanguage = Language::JAPANESE;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'TT',           // name
            'ブラック',     // lyricist
            'ブラック',     // composer
            'ブラック',     // arranger
        ]);

        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, $targetLanguage);

        $translatedBasic = $result->translatedBasic();
        $this->assertSame('TT', (string) $translatedBasic->name());
        $this->assertInstanceOf(SongBasic::class, $translatedBasic);
        /** @var SongBasic $translatedBasic */
        $this->assertSame('ブラック', (string) $translatedBasic->lyricist());
        $this->assertSame('ブラック', (string) $translatedBasic->composer());
        $this->assertSame('ブラック', (string) $translatedBasic->arranger());
    }

    /**
     * 正常系：セクションのTextBlockが翻訳されること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateWikiWithSections(): void
    {
        $sections = new SectionContentCollection([
            new Section(
                '경력',
                0,
                new SectionContentCollection([
                    new TextBlock(0, '채영은 트와이스의 멤버입니다.'),
                ]),
            ),
        ]);

        $wiki = $this->createTalentWiki($sections);
        $targetLanguage = Language::JAPANESE;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'チェヨン',                                 // name
            'ソン・チェヨン',                           // real_name
            'キャリア',                                 // section title
            'チェヨンはTWICEのメンバーです。',           // text block content
        ]);

        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, $targetLanguage);

        $translatedSections = $result->translatedSections();
        $this->assertCount(1, $translatedSections->all());

        $section = $translatedSections->all()[0];
        $this->assertInstanceOf(Section::class, $section);
        /** @var Section $section */
        $this->assertSame('キャリア', $section->title());

        $blocks = $section->contents()->all();
        $this->assertCount(1, $blocks);
        $this->assertInstanceOf(TextBlock::class, $blocks[0]);
        /** @var TextBlock $textBlock */
        $textBlock = $blocks[0];
        $this->assertSame('チェヨンはTWICEのメンバーです。', $textBlock->content());
    }

    #[TestWith([Language::ENGLISH, 'overview', 'Overview', 'sources', 'Sources'])]
    #[TestWith([Language::ENGLISH, 'Overview', 'Overview', 'source', 'Source'])]
    #[TestWith([Language::JAPANESE, '概要', '概要', '出典', '出典'])]
    #[TestWith([Language::KOREAN, '개요', '개요', '출처', '출처'])]
    public function testCapitalizesEnglishSectionHeadingsIncludingNestedSections(
        Language $targetLanguage,
        string $overviewTranslation,
        string $expectedOverview,
        string $sourceTranslation,
        string $expectedSource,
    ): void {
        $sections = new SectionContentCollection([
            new Section('概要', 0, new SectionContentCollection([
                new TextBlock(0, '紹介文'),
                new Section('出典', 1, new SectionContentCollection([]), 2),
            ])),
        ]);
        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'Chaeyoung', 'Son Chaeyoung', $overviewTranslation, 'translated body', $sourceTranslation,
        ]);
        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($this->createTalentWiki($sections), $targetLanguage);

        $overview = $result->translatedSections()->all()[0];
        self::assertInstanceOf(Section::class, $overview);
        self::assertSame($expectedOverview, $overview->title());
        $body = $overview->contents()->all()[0];
        self::assertInstanceOf(TextBlock::class, $body);
        self::assertSame('translated body', $body->content());
        $source = $overview->contents()->all()[1];
        self::assertInstanceOf(Section::class, $source);
        self::assertSame($expectedSource, $source->title());
        self::assertSame(2, $source->depth());
    }

    /**
     * 正常系：QuoteBlock、ListBlock、TableBlockが翻訳されること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateWikiWithVariousBlocks(): void
    {
        $sections = new SectionContentCollection([
            new Section(
                '인용',
                0,
                new SectionContentCollection([
                    new QuoteBlock(0, '음악은 세계 공통 언어입니다.', '채영'),
                    new ListBlock(1, ListType::BULLET, ['항목1', '항목2']),
                    new TableBlock(
                        2,
                        [[new TableCell('셀1'), new TableCell('셀2', 2)]],
                        [new TableCell('헤더1'), new TableCell('헤더2')],
                        '75%',
                    ),
                ]),
            ),
        ]);

        $wiki = $this->createTalentWiki($sections);
        $targetLanguage = Language::JAPANESE;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'チェヨン',                             // name
            'ソン・チェヨン',                       // real_name
            '引用',                                 // section title
            '音楽は世界共通の言語です。',           // quote content
            'チェヨン',                             // quote source
            '項目1',                               // list item 1
            '項目2',                               // list item 2
            'ヘッダー1',                           // table header 1
            'ヘッダー2',                           // table header 2
            'セル1',                               // table cell 1
            'セル2',                               // table cell 2
        ]);

        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, $targetLanguage);

        $section = $result->translatedSections()->all()[0];
        /** @var Section $section */
        $blocks = $section->contents()->all();

        /** @var QuoteBlock $quoteBlock */
        $quoteBlock = $blocks[0];
        $this->assertSame('音楽は世界共通の言語です。', $quoteBlock->content());
        $this->assertSame('チェヨン', $quoteBlock->source());

        /** @var ListBlock $listBlock */
        $listBlock = $blocks[1];
        $this->assertSame(['項目1', '項目2'], $listBlock->items());

        /** @var TableBlock $tableBlock */
        $tableBlock = $blocks[2];
        $this->assertEquals([new TableCell('ヘッダー1'), new TableCell('ヘッダー2')], $tableBlock->headerCells());
        $this->assertEquals(
            [[new TableCell('セル1'), new TableCell('セル2', 2)]],
            $tableBlock->rowCells()
        );
        $this->assertSame('75%', $tableBlock->tableWidth());
    }

    public function testTranslatesMediaDescriptionsAndPreservesReferences(): void
    {
        $imageId = new ImageIdentifier(StrTestHelper::generateUuid());
        $profileId = new WikiIdentifier(StrTestHelper::generateUuid());
        $sections = new SectionContentCollection([
            new Section('미디어', 0, new SectionContentCollection([
                new ImageBlock(0, $imageId, '사진 설명', '대체 텍스트'),
                new ImageGalleryBlock(1, [$imageId], '갤러리 설명'),
                new EmbedBlock(2, EmbedProvider::YOUTUBE, 'abc123', '영상 설명'),
                new ProfileCardListBlock(3, [$profileId], '관련 인물'),
                new Section('상세', 4, new SectionContentCollection([new TextBlock(0, '본문')]), 2),
            ])),
        ]);
        $client = $this->createGoogleTranslateClientMock([
            'チェヨン', 'ソン・チェヨン', 'メディア', '写真の説明', '代替テキスト',
            'ギャラリーの説明', '動画の説明', '関連人物', '詳細', '本文',
        ]);
        $this->app()->instance(GoogleTranslateClient::class, $client);

        $result = $this->app()->make(TranslationServiceInterface::class)
            ->translateWiki($this->createTalentWiki($sections), Language::JAPANESE);
        $section = $result->translatedSections()->all()[0];
        self::assertInstanceOf(Section::class, $section);
        self::assertSame('メディア', $section->title());
        [$image, $gallery, $embed, $profiles, $nested] = $section->contents()->all();
        self::assertInstanceOf(ImageBlock::class, $image);
        self::assertSame('写真の説明', $image->caption());
        self::assertSame('代替テキスト', $image->alt());
        self::assertSame($imageId, $image->imageIdentifier());
        self::assertInstanceOf(ImageGalleryBlock::class, $gallery);
        self::assertSame('ギャラリーの説明', $gallery->caption());
        self::assertSame([$imageId], $gallery->imageIdentifiers());
        self::assertInstanceOf(EmbedBlock::class, $embed);
        self::assertSame('動画の説明', $embed->caption());
        self::assertSame('abc123', $embed->embedId());
        self::assertSame(EmbedProvider::YOUTUBE, $embed->provider());
        self::assertInstanceOf(ProfileCardListBlock::class, $profiles);
        self::assertSame('関連人物', $profiles->title());
        self::assertSame([$profileId], $profiles->wikiIdentifiers());
        self::assertSame(3, $profiles->displayOrder());
        self::assertInstanceOf(Section::class, $nested);
        self::assertSame('詳細', $nested->title());
        self::assertSame(2, $nested->depth());
        $text = $nested->contents()->all()[0];
        self::assertInstanceOf(TextBlock::class, $text);
        self::assertSame('本文', $text->content());
    }

    public function testKeepsAbsentMediaDescriptionsAndDoesNotShiftFollowingText(): void
    {
        $imageId = new ImageIdentifier(StrTestHelper::generateUuid());
        $sections = new SectionContentCollection([
            new ImageBlock(0, $imageId, null, ''),
            new ImageGalleryBlock(1, [$imageId]),
            new EmbedBlock(2, EmbedProvider::YOUTUBE, 'abc123', ''),
            new ProfileCardListBlock(3, []),
            new TextBlock(4, '본문'),
        ]);
        $client = $this->createGoogleTranslateClientMock(['チェヨン', 'ソン・チェヨン', '本文']);
        $this->app()->instance(GoogleTranslateClient::class, $client);
        $result = $this->app()->make(TranslationServiceInterface::class)
            ->translateWiki($this->createTalentWiki($sections), Language::JAPANESE);
        [$image, $gallery, $embed, $profiles, $text] = $result->translatedSections()->all();
        self::assertInstanceOf(ImageBlock::class, $image);
        self::assertNull($image->caption());
        self::assertSame('', $image->alt());
        self::assertInstanceOf(ImageGalleryBlock::class, $gallery);
        self::assertNull($gallery->caption());
        self::assertInstanceOf(EmbedBlock::class, $embed);
        self::assertSame('', $embed->caption());
        self::assertInstanceOf(ProfileCardListBlock::class, $profiles);
        self::assertNull($profiles->title());
        self::assertInstanceOf(TextBlock::class, $text);
        self::assertSame('本文', $text->content());
    }

    public function testTranslatesGroupSymbolFandomAndColorLabels(): void
    {
        $wiki = $this->createGroupWiki([
            'representative_symbol' => '별', 'fandom_name' => '원스', 'emoji' => '⭐',
            'official_colors' => [['color_code' => '#ff0055', 'label' => '빨강']],
        ]);
        $this->app()->instance(GoogleTranslateClient::class, $this->createGoogleTranslateClientMock([
            'TWICE', '星', 'ワンス', '赤',
        ]));
        $result = $this->app()->make(TranslationServiceInterface::class)->translateWiki($wiki, Language::JAPANESE);
        $basic = $result->translatedBasic();
        self::assertInstanceOf(GroupBasic::class, $basic);
        self::assertSame('星', $basic->representativeSymbol()->value());
        self::assertSame('ワンス', $basic->fandomName()->value());
        self::assertSame('赤', $basic->officialColors()[0]->label());
        self::assertSame('#ff0055', (string) $basic->officialColors()[0]->colorCode());
        self::assertSame('⭐', $basic->emoji()->value());
    }

    public function testTranslatesTalentFandom(): void
    {
        $wiki = $this->createTalentWiki(fandomName: '팬덤');
        $this->app()->instance(GoogleTranslateClient::class, $this->createGoogleTranslateClientMock(['チェヨン', 'ソン・チェヨン', 'ファンダム']));
        $basic = $this->app()->make(TranslationServiceInterface::class)->translateWiki($wiki, Language::JAPANESE)->translatedBasic();
        self::assertInstanceOf(TalentBasic::class, $basic);
        self::assertSame('ファンダム', $basic->fandomName()->value());
    }

    public function testTranslatesSongAlbum(): void
    {
        $wiki = $this->createSongWiki('앨범');
        $this->app()->instance(GoogleTranslateClient::class, $this->createGoogleTranslateClientMock(['TT', 'ブラック', 'ブラック', 'ブラック', 'アルバム']));
        $basic = $this->app()->make(TranslationServiceInterface::class)->translateWiki($wiki, Language::JAPANESE)->translatedBasic();
        self::assertInstanceOf(SongBasic::class, $basic);
        self::assertSame('アルバム', $basic->albumName());
    }

    public function testBoundsTranslatedAlbumToStorageLimit(): void
    {
        $this->app()->instance(GoogleTranslateClient::class, $this->createGoogleTranslateClientMock([
            'TT', 'ブラック', 'ブラック', 'ブラック', str_repeat('あ', 300),
        ]));
        $basic = $this->app()->make(TranslationServiceInterface::class)
            ->translateWiki($this->createSongWiki('앨범'), Language::JAPANESE)->translatedBasic();
        self::assertInstanceOf(SongBasic::class, $basic);
        self::assertSame(255, mb_strlen($basic->albumName() ?? ''));
    }

    public function testTranslatesSeoMetadataAfterSectionContent(): void
    {
        $wiki = $this->createTalentWiki(new SectionContentCollection([new TextBlock(0, '본문')]));
        $wiki->setTitle(new SeoTitle('제목'));
        $wiki->setMetaDescription(new MetaDescription('설명'));
        $wiki->setKeywords(new SeoKeywords(['키워드', '검색']));
        $this->app()->instance(GoogleTranslateClient::class, $this->createGoogleTranslateClientMock([
            'チェヨン', 'ソン・チェヨン', '本文', 'タイトル', '説明文', 'キーワード', '検索',
        ]));
        $result = $this->app()->make(TranslationServiceInterface::class)->translateWiki($wiki, Language::JAPANESE);
        self::assertSame('タイトル', (string) $result->translatedTitle());
        self::assertSame('説明文', (string) $result->translatedMetaDescription());
        self::assertSame(['キーワード', '検索'], $result->translatedKeywords()?->values());
    }

    public function testKeepsNullAndEmptySeoMetadata(): void
    {
        $wiki = $this->createTalentWiki();
        $wiki->setMetaDescription(new MetaDescription(''));
        $wiki->setKeywords(new SeoKeywords([]));
        $this->app()->instance(GoogleTranslateClient::class, $this->createGoogleTranslateClientMock(['チェヨン', 'ソン・チェヨン']));
        $result = $this->app()->make(TranslationServiceInterface::class)->translateWiki($wiki, Language::JAPANESE);
        self::assertNull($result->translatedTitle());
        self::assertSame('', (string) $result->translatedMetaDescription());
        self::assertSame([], $result->translatedKeywords()?->values());
    }

    public function testBoundsExpandedTranslationsAndPreservesOriginalForBlankResults(): void
    {
        $wiki = $this->createGroupWiki([
            'representative_symbol' => '별', 'fandom_name' => '원스',
            'official_colors' => [['color_code' => '#ff0055', 'label' => '빨강']],
        ]);
        $wiki->setTitle(new SeoTitle('제목'));
        $wiki->setMetaDescription(new MetaDescription('설명'));
        $wiki->setKeywords(new SeoKeywords(['키워드']));
        $longText = str_repeat('あ', 200);
        $this->app()->instance(GoogleTranslateClient::class, $this->createGoogleTranslateClientMock([
            'TWICE', $longText, '', $longText, $longText, $longText, $longText,
        ]));
        $result = $this->app()->make(TranslationServiceInterface::class)->translateWiki($wiki, Language::JAPANESE);
        $basic = $result->translatedBasic();
        self::assertInstanceOf(GroupBasic::class, $basic);
        self::assertSame(32, mb_strlen($basic->representativeSymbol()->value()));
        self::assertSame('원스', $basic->fandomName()->value());
        self::assertSame(Color::MAX_LABEL_LENGTH, mb_strlen($basic->officialColors()[0]->label()));
        self::assertSame(SeoTitle::MAX_LENGTH, mb_strlen((string) $result->translatedTitle()));
        self::assertSame(MetaDescription::MAX_LENGTH, mb_strlen((string) $result->translatedMetaDescription()));
        self::assertSame(SeoKeywords::MAX_KEYWORD_LENGTH, mb_strlen($result->translatedKeywords()?->values()[0] ?? ''));
    }

    /**
     * 異常系：翻訳結果が空の場合、元の値がフォールバックとして使用されること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateWikiUsesOriginalValuesWhenTranslationIsEmpty(): void
    {
        $wiki = $this->createTalentWiki();
        $targetLanguage = Language::ENGLISH;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([]);

        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, $targetLanguage);

        $translatedBasic = $result->translatedBasic();
        $this->assertSame('채영', (string) $translatedBasic->name());
        /** @var TalentBasic $translatedBasic */
        $this->assertSame('손채영', (string) $translatedBasic->realName());
    }

    /**
     * 正常系：GoogleTranslateClientに正しいパラメータが渡されること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateWikiCallsClientWithCorrectParameters(): void
    {
        $wiki = $this->createTalentWiki();
        $targetLanguage = Language::JAPANESE;

        $capturedRequest = null;

        /** @var MockInterface&GoogleTranslateClient $googleTranslateClient */
        $googleTranslateClient = Mockery::mock(GoogleTranslateClient::class);
        $googleTranslateClient->shouldReceive('translateTexts')
            ->once()
            ->withArgs(function (TranslateTextsRequest $request) use (&$capturedRequest): bool {
                $capturedRequest = $request;

                return true;
            })
            ->andReturn(new TranslateTextsResponse(['チェヨン', 'ソン・チェヨン']));

        $this->app()->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app()->make(TranslationServiceInterface::class);
        $service->translateWiki($wiki, $targetLanguage);

        $this->assertNotNull($capturedRequest);
        $this->assertSame(['채영', '손채영'], $capturedRequest->texts());
        $this->assertSame($targetLanguage->value, $capturedRequest->targetLanguage());
    }

    /**
     * 異常系：未対応のBasicタイプの場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateWikiThrowsExceptionForUnsupportedBasicType(): void
    {
        /** @var MockInterface&BasicInterface $basic */
        $basic = Mockery::mock(BasicInterface::class);
        $basic->shouldReceive('getBasicType')->andReturn('unknown');
        $basic->shouldReceive('toArray')->andReturn(['type' => 'unknown']);

        $wiki = new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('gr-test-wiki'),
            Language::KOREAN,
            ResourceType::TALENT,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );

        $service = $this->app()->make(TranslationServiceInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported basic type: unknown');
        $service->translateWiki($wiki, Language::JAPANESE);
    }

    /**
     * 異常系：toArrayのtypeが未対応の場合、Basic再構築時に例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateWikiThrowsExceptionForUnsupportedBasicTypeOnReconstruct(): void
    {
        /** @var MockInterface&BasicInterface $basic */
        $basic = Mockery::mock(BasicInterface::class);
        $basic->shouldReceive('getBasicType')->andReturn('talent');
        $basic->shouldReceive('toArray')->andReturn([
            'type' => 'unknown',
            'name' => 'test',
            'real_name' => 'test',
        ]);

        $wiki = new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('gr-test-wiki'),
            Language::KOREAN,
            ResourceType::TALENT,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );

        $service = $this->app()->make(TranslationServiceInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported basic type: unknown');
        $service->translateWiki($wiki, Language::JAPANESE);
    }

    /**
     * @param string[] $translations
     * @return MockInterface&GoogleTranslateClient
     */
    private function createGoogleTranslateClientMock(array $translations): MockInterface
    {
        /** @var MockInterface&GoogleTranslateClient $mock */
        $mock = Mockery::mock(GoogleTranslateClient::class);
        $mock->shouldReceive('translateTexts')
            ->once()
            ->andReturn(new TranslateTextsResponse($translations));

        return $mock;
    }

    private function createTalentWiki(
        ?SectionContentCollection $sections = null,
        string $representativeSymbol = '',
        string $position = '',
        string $fandomName = '',
    ): Wiki {
        $basic = TalentBasic::fromArray([
            'type' => 'talent',
            'name' => '채영',
            'normalized_name' => 'ㅊㅇ',
            'real_name' => '손채영',
            'normalized_real_name' => 'ㅅㅊㅇ',
            'birthday' => null,
            'agency_identifier' => null,
            'group_identifiers' => [],
            'emoji' => '',
            'representative_symbol' => $representativeSymbol,
            'position' => $position,
            'fandom_name' => $fandomName,
        ]);

        return new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('tl-chaeyoung'),
            Language::KOREAN,
            ResourceType::TALENT,
            $basic,
            $sections ?? new SectionContentCollection(),
            null,
            new Version(1),
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createGroupWiki(array $overrides = []): Wiki
    {
        $basic = GroupBasic::fromArray(array_replace([
            'type' => 'group',
            'name' => '트와이스',
            'normalized_name' => 'ㅌㅇㅇㅅ',
            'agency_identifier' => null,
            'emoji' => '',
            'representative_symbol' => '',
            'fandom_name' => '',
        ], $overrides));

        return new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('gr-twice'),
            Language::KOREAN,
            ResourceType::GROUP,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );
    }

    private function createSongWiki(?string $albumName = null): Wiki
    {
        $basic = SongBasic::fromArray([
            'type' => 'song',
            'name' => 'TT',
            'album_name' => $albumName,
            'normalized_name' => 'tt',
            'lyricist' => '블랙',
            'normalized_lyricist' => 'ㅂㄹ',
            'composer' => '블랙',
            'normalized_composer' => 'ㅂㄹ',
            'arranger' => '블랙',
            'normalized_arranger' => 'ㅂㄹ',
        ]);

        return new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('sg-twice-tt'),
            Language::KOREAN,
            ResourceType::SONG,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );
    }

    private function createAgencyWiki(): Wiki
    {
        $basic = AgencyBasic::fromArray([
            'type' => 'agency',
            'name' => 'JYP엔터테인먼트',
            'normalized_name' => 'jypㅇㅌㅌㅇㅁㅌ',
            'ceo' => '박진영',
            'normalized_ceo' => 'ㅂㅈㅇ',
            'founded_in' => null,
            'parent_agency_identifier' => null,
            'status' => null,
            'official_website' => null,
            'social_links' => [],
        ]);

        return new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('ag-jyp-entertainment'),
            Language::KOREAN,
            ResourceType::AGENCY,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );
    }
}
