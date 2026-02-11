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
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\QuoteBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
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
        $service = $this->app->make(TranslationServiceInterface::class);

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

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, $targetLanguage);

        $translatedBasic = $result->translatedBasic();
        $this->assertSame('チェヨン', (string) $translatedBasic->name());
        $this->assertInstanceOf(TalentBasic::class, $translatedBasic);
        /** @var TalentBasic $translatedBasic */
        $this->assertSame('ソン・チェヨン', (string) $translatedBasic->realName());
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

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);
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

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);
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

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);
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

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);
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
                    new TableBlock(2, [['셀1', '셀2']], ['헤더1', '헤더2']),
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

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);
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
        $this->assertSame(['ヘッダー1', 'ヘッダー2'], $tableBlock->headers());
        $this->assertSame([['セル1', 'セル2']], $tableBlock->rows());
    }

    /**
     * 正常系：翻訳不要なブロック（ImageBlock, EmbedBlock等）はそのまま返されること.
     *
     * @throws BindingResolutionException
     */
    public function testTranslateWikiWithUntranslatableBlocks(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $imageBlock = new ImageBlock(0, $imageIdentifier, 'caption', 'alt');
        $embedBlock = new EmbedBlock(1, EmbedProvider::YOUTUBE, 'abc123', 'embed caption');

        $sections = new SectionContentCollection([
            new Section(
                '미디어',
                0,
                new SectionContentCollection([
                    $imageBlock,
                    $embedBlock,
                ]),
            ),
        ]);

        $wiki = $this->createTalentWiki($sections);
        $targetLanguage = Language::JAPANESE;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'チェヨン',       // name
            'ソン・チェヨン', // real_name
            'メディア',       // section title
        ]);

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);
        $result = $service->translateWiki($wiki, $targetLanguage);

        $section = $result->translatedSections()->all()[0];
        /** @var Section $section */
        $this->assertSame('メディア', $section->title());

        $blocks = $section->contents()->all();
        $this->assertCount(2, $blocks);

        $this->assertInstanceOf(ImageBlock::class, $blocks[0]);
        $this->assertSame($imageBlock, $blocks[0]);

        $this->assertInstanceOf(EmbedBlock::class, $blocks[1]);
        $this->assertSame($embedBlock, $blocks[1]);
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

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);
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

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);
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
            new Slug('test-wiki'),
            Language::KOREAN,
            ResourceType::TALENT,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );

        $service = $this->app->make(TranslationServiceInterface::class);

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
            new Slug('test-wiki'),
            Language::KOREAN,
            ResourceType::TALENT,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );

        $googleTranslateClient = $this->createGoogleTranslateClientMock([
            'translated-name',
            'translated-real-name',
        ]);

        $this->app->instance(GoogleTranslateClient::class, $googleTranslateClient);

        $service = $this->app->make(TranslationServiceInterface::class);

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

    private function createTalentWiki(?SectionContentCollection $sections = null): Wiki
    {
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
            'representative_symbol' => '',
            'position' => '',
            'fandom_name' => '',
            'profile_image_identifier' => null,
        ]);

        return new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('chaeyoung'),
            Language::KOREAN,
            ResourceType::TALENT,
            $basic,
            $sections ?? new SectionContentCollection(),
            null,
            new Version(1),
        );
    }

    private function createGroupWiki(): Wiki
    {
        $basic = GroupBasic::fromArray([
            'type' => 'group',
            'name' => '트와이스',
            'normalized_name' => 'ㅌㅇㅇㅅ',
            'agency_identifier' => null,
            'emoji' => '',
            'representative_symbol' => '',
            'fandom_name' => '',
        ]);

        return new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('twice'),
            Language::KOREAN,
            ResourceType::GROUP,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );
    }

    private function createSongWiki(): Wiki
    {
        $basic = SongBasic::fromArray([
            'type' => 'song',
            'name' => 'TT',
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
            new Slug('twice-tt'),
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
            'logo_image_identifier' => null,
            'official_website' => null,
            'social_links' => [],
        ]);

        return new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('jyp-entertainment'),
            Language::KOREAN,
            ResourceType::AGENCY,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );
    }
}
