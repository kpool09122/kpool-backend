<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Service;

use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyRequest;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyResponse;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupRequest;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupResponse;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongRequest;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongResponse;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use JsonException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\AutoWikiCreationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Infrastructure\Service\AutoWikiCreationService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutoWikiCreationServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(AutoWikiCreationServiceInterface::class);

        $this->assertInstanceOf(AutoWikiCreationService::class, $service);
    }

    // ========================================================================
    // Agency
    // ========================================================================

    /**
     * 正常系: Agency の情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateAgencyWithFullData(): void
    {
        $description = 'JYP Entertainmentは韓国の大手エンターテインメント企業です。';

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'JYP Entertainment',
                                    'ceo_name' => 'J.Y. Park',
                                    'founded_year' => 1997,
                                    'description' => $description,
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://www.jypentertainment.com/',
                                    'title' => 'JYP Entertainment 公式サイト',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateAgency')
            ->once()
            ->withArgs(fn (GenerateAgencyRequest $r) => $r->agencyName() === 'JYP엔터테인먼트')
            ->andReturn(new GenerateAgencyResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::AGENCY,
            name: new Name('JYP엔터테인먼트'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('JYP Entertainment', $result->alphabetName());
        $this->assertInstanceOf(AgencyBasic::class, $result->basic());
        $this->assertSame('JYP엔터테인먼트', (string) $result->basic()->name());

        /** @var AgencyBasic $basic */
        $basic = $result->basic();
        $this->assertSame('J.Y. Park', (string) $basic->ceo());
        $this->assertNotNull($basic->foundedIn());

        $this->assertFalse($result->sections()->isEmpty());
        $blocks = $result->sections()->blocks();
        $this->assertCount(1, $blocks);
        $this->assertInstanceOf(TextBlock::class, $blocks[0]);
        /** @var TextBlock $textBlock */
        $textBlock = $blocks[0];
        $this->assertSame($description, $textBlock->content());

        $this->assertCount(1, $result->sources());
        $this->assertSame('https://www.jypentertainment.com/', $result->sources()[0]->uri());
    }

    /**
     * 異常系: Agency生成でGemini API例外が発生した場合、空のデータが返されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateAgencyReturnsEmptyDataOnException(): void
    {
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateAgency')
            ->once()
            ->andThrow(new RuntimeException('Gemini API rate limit exceeded'));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::KOREAN,
            resourceType: ResourceType::AGENCY,
            name: new Name('SM엔터테인먼트'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertNull($result->alphabetName());
        $this->assertInstanceOf(AgencyBasic::class, $result->basic());
        $this->assertTrue($result->sections()->isEmpty());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: Agencyのdescriptionがnullの場合、セクションが空であること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateAgencyWithNullDescription(): void
    {
        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'HYBE',
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateAgency')
            ->once()
            ->andReturn(new GenerateAgencyResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::AGENCY,
            name: new Name('HYBE'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('HYBE', $result->alphabetName());
        $this->assertTrue($result->sections()->isEmpty());
    }

    // ========================================================================
    // Group
    // ========================================================================

    /**
     * 正常系: Group の情報が正しく生成され、事務所名が解決されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateGroupWithAgencyNameResolution(): void
    {
        $agencyId = new WikiIdentifier(StrTestHelper::generateUuid());
        $description = 'TWICEは韓国の9人組ガールズグループです。';

        $agencyWiki = $this->createWikiMock('JYP Entertainment');

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($agencyId)
            ->andReturn($agencyWiki);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'TWICE',
            'description' => $description,
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->withArgs(function (GenerateGroupRequest $r) {
                return $r->groupName() === '트와이스'
                    && $r->agencyName() === 'JYP Entertainment';
            })
            ->andReturn(new GenerateGroupResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::KOREAN,
            resourceType: ResourceType::GROUP,
            name: new Name('트와이스'),
            agencyIdentifier: $agencyId,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('TWICE', $result->alphabetName());
        $this->assertInstanceOf(GroupBasic::class, $result->basic());
        $this->assertSame('트와이스', (string) $result->basic()->name());

        /** @var GroupBasic $basic */
        $basic = $result->basic();
        $this->assertSame((string) $agencyId, (string) $basic->agencyIdentifier());

        $this->assertFalse($result->sections()->isEmpty());
        $blocks = $result->sections()->blocks();
        $this->assertInstanceOf(TextBlock::class, $blocks[0]);
        /** @var TextBlock $textBlock */
        $textBlock = $blocks[0];
        $this->assertSame($description, $textBlock->content());
    }

    /**
     * 異常系: Group生成でGemini API例外が発生した場合、空のデータが返されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateGroupReturnsEmptyDataOnException(): void
    {
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->andThrow(new RuntimeException('API error'));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::GROUP,
            name: new Name('aespa'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertNull($result->alphabetName());
        $this->assertInstanceOf(GroupBasic::class, $result->basic());
        $this->assertTrue($result->sections()->isEmpty());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: Group生成でAgencyIdentifierがnullの場合、AgencyNameがnullであること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateGroupWithoutAgencyIdentifier(): void
    {
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldNotReceive('findById');

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'BTS',
            'description' => 'BTSは世界的に有名なK-POPグループです。',
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->withArgs(fn (GenerateGroupRequest $r) => $r->agencyName() === null)
            ->andReturn(new GenerateGroupResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::GROUP,
            name: new Name('BTS'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('BTS', $result->alphabetName());
    }

    /**
     * 正常系: Group生成でAgencyのWikiが見つからない場合、AgencyNameがnullであること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateGroupWithAgencyNotFound(): void
    {
        $agencyId = new WikiIdentifier(StrTestHelper::generateUuid());

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($agencyId)
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'TWICE',
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->withArgs(fn (GenerateGroupRequest $r) => $r->agencyName() === null)
            ->andReturn(new GenerateGroupResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::KOREAN,
            resourceType: ResourceType::GROUP,
            name: new Name('트와이스'),
            agencyIdentifier: $agencyId,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('TWICE', $result->alphabetName());
    }

    // ========================================================================
    // Talent
    // ========================================================================

    /**
     * 正常系: Talent の情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateTalentWithFullData(): void
    {
        $agencyId = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupId = new WikiIdentifier(StrTestHelper::generateUuid());
        $description = 'ジミンはBTSのメンバーです。';

        $agencyWiki = $this->createWikiMock('HYBE');
        $groupWiki = $this->createWikiMock('BTS');

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($agencyId)
            ->once()
            ->andReturn($agencyWiki);
        $wikiRepository->shouldReceive('findById')
            ->with($groupId)
            ->once()
            ->andReturn($groupWiki);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'Jimin',
            'real_name' => '박지민',
            'birthday' => '1995-10-13',
            'description' => $description,
        ], [
            ['uri' => 'https://example.com/', 'title' => 'Example'],
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->withArgs(function (GenerateTalentRequest $r) {
                return $r->talentName() === '지민'
                    && $r->agencyName() === 'HYBE'
                    && $r->groupNames() === ['BTS'];
            })
            ->andReturn(new GenerateTalentResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::TALENT,
            name: new Name('지민'),
            agencyIdentifier: $agencyId,
            groupIdentifiers: [$groupId],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('Jimin', $result->alphabetName());
        $this->assertInstanceOf(TalentBasic::class, $result->basic());
        $this->assertSame('지민', (string) $result->basic()->name());

        /** @var TalentBasic $basic */
        $basic = $result->basic();
        $this->assertSame('박지민', (string) $basic->realName());
        $this->assertNotNull($basic->birthday());
        $this->assertSame((string) $agencyId, (string) $basic->agencyIdentifier());
        $this->assertCount(1, $basic->groupIdentifiers());
        $this->assertSame((string) $groupId, (string) $basic->groupIdentifiers()[0]);

        $this->assertFalse($result->sections()->isEmpty());
        $this->assertCount(1, $result->sources());
    }

    /**
     * 異常系: Talent生成でGemini API例外が発生した場合、空のデータが返されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateTalentReturnsEmptyDataOnException(): void
    {
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->andThrow(new RuntimeException('API error'));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::KOREAN,
            resourceType: ResourceType::TALENT,
            name: new Name('카리나'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertNull($result->alphabetName());
        $this->assertInstanceOf(TalentBasic::class, $result->basic());
        $this->assertTrue($result->sections()->isEmpty());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: 不正なbirthdayの場合、birthdayがnullになること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateTalentWithInvalidBirthday(): void
    {
        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'Karina',
            'real_name' => '유지민',
            'birthday' => 'invalid-date',
            'description' => 'カリナはaespaのメンバーです。',
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->andReturn(new GenerateTalentResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::TALENT,
            name: new Name('카리나'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        /** @var TalentBasic $basic */
        $basic = $result->basic();
        $this->assertNull($basic->birthday());
        $this->assertSame('Karina', $result->alphabetName());
    }

    /**
     * 正常系: 複数のGroupIdentifiersがある場合、全てのグループ名が解決されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateTalentWithMultipleGroups(): void
    {
        $groupId1 = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupId2 = new WikiIdentifier(StrTestHelper::generateUuid());

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($groupId1)
            ->once()
            ->andReturn($this->createWikiMock('BTS'));
        $wikiRepository->shouldReceive('findById')
            ->with($groupId2)
            ->once()
            ->andReturn($this->createWikiMock('TXT'));

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'Test',
            'description' => 'Test description.',
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->withArgs(fn (GenerateTalentRequest $r) => $r->groupNames() === ['BTS', 'TXT'])
            ->andReturn(new GenerateTalentResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::TALENT,
            name: new Name('テスト'),
            agencyIdentifier: null,
            groupIdentifiers: [$groupId1, $groupId2],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('Test', $result->alphabetName());
    }

    /**
     * 正常系: GroupのWikiが見つからない場合、そのグループ名はスキップされること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateTalentWithGroupNotFound(): void
    {
        $groupId1 = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupId2 = new WikiIdentifier(StrTestHelper::generateUuid());

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($groupId1)
            ->once()
            ->andReturn($this->createWikiMock('BTS'));
        $wikiRepository->shouldReceive('findById')
            ->with($groupId2)
            ->once()
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'Test',
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->withArgs(fn (GenerateTalentRequest $r) => $r->groupNames() === ['BTS'])
            ->andReturn(new GenerateTalentResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::TALENT,
            name: new Name('テスト'),
            agencyIdentifier: null,
            groupIdentifiers: [$groupId1, $groupId2],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('Test', $result->alphabetName());
    }

    // ========================================================================
    // Song
    // ========================================================================

    /**
     * 正常系: Song の情報が正しく生成され、関連エンティティが解決されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateSongWithFullData(): void
    {
        $agencyId = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupId = new WikiIdentifier(StrTestHelper::generateUuid());
        $talentId = new WikiIdentifier(StrTestHelper::generateUuid());
        $overview = 'Dynamiteは2020年にリリースされたBTSの楽曲です。';

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($agencyId)
            ->once()
            ->andReturn($this->createWikiMock('HYBE'));
        $wikiRepository->shouldReceive('findById')
            ->with($groupId)
            ->once()
            ->andReturn($this->createWikiMock('BTS'));
        $wikiRepository->shouldReceive('findById')
            ->with($talentId)
            ->once()
            ->andReturn($this->createWikiMock('Jimin'));

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'Dynamite',
            'lyricist' => 'David Stewart',
            'composer' => 'Jessica Agombar',
            'release_date' => '2020-08-21',
            'overview' => $overview,
        ], [
            ['uri' => 'https://example.com/dynamite', 'title' => 'Dynamite - Wikipedia'],
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(function (GenerateSongRequest $r) {
                return $r->songName() === 'Dynamite'
                    && $r->agencyName() === 'HYBE'
                    && $r->groupName() === 'BTS'
                    && $r->talentName() === 'Jimin';
            })
            ->andReturn(new GenerateSongResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::SONG,
            name: new Name('Dynamite'),
            agencyIdentifier: $agencyId,
            groupIdentifiers: [$groupId],
            talentIdentifiers: [$talentId],
        );

        $result = $service->generate($payload);

        $this->assertSame('Dynamite', $result->alphabetName());
        $this->assertInstanceOf(SongBasic::class, $result->basic());
        $this->assertSame('Dynamite', (string) $result->basic()->name());

        /** @var SongBasic $basic */
        $basic = $result->basic();
        $this->assertSame('David Stewart', (string) $basic->lyricist());
        $this->assertSame('Jessica Agombar', (string) $basic->composer());
        $this->assertSame((string) $agencyId, (string) $basic->agencyIdentifier());
        $this->assertCount(1, $basic->groupIdentifiers());
        $this->assertCount(1, $basic->talentIdentifiers());

        $this->assertFalse($result->sections()->isEmpty());
        $blocks = $result->sections()->blocks();
        $this->assertInstanceOf(TextBlock::class, $blocks[0]);
        /** @var TextBlock $textBlock */
        $textBlock = $blocks[0];
        $this->assertSame($overview, $textBlock->content());

        $this->assertCount(1, $result->sources());
    }

    /**
     * 異常系: Song生成でGemini API例外が発生した場合、空のデータが返されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateSongReturnsEmptyDataOnException(): void
    {
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->andThrow(new RuntimeException('API error'));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::SONG,
            name: new Name('Dynamite'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertNull($result->alphabetName());
        $this->assertInstanceOf(SongBasic::class, $result->basic());
        $this->assertTrue($result->sections()->isEmpty());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: Song生成で関連エンティティがない場合、全てnullでリクエストされること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateSongWithoutRelations(): void
    {
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldNotReceive('findById');

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'Test Song',
            'overview' => 'A test song.',
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(function (GenerateSongRequest $r) {
                return $r->agencyName() === null
                    && $r->groupName() === null
                    && $r->talentName() === null;
            })
            ->andReturn(new GenerateSongResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::ENGLISH,
            resourceType: ResourceType::SONG,
            name: new Name('Test Song'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('Test Song', $result->alphabetName());
    }

    /**
     * 正常系: Song生成で複数のGroupがある場合、最初のグループ名のみが使用されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateSongUsesFirstGroupName(): void
    {
        $groupId1 = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupId2 = new WikiIdentifier(StrTestHelper::generateUuid());

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($groupId1)
            ->once()
            ->andReturn($this->createWikiMock('BTS'));
        $wikiRepository->shouldReceive('findById')
            ->with($groupId2)
            ->once()
            ->andReturn($this->createWikiMock('TXT'));

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'Test',
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(fn (GenerateSongRequest $r) => $r->groupName() === 'BTS')
            ->andReturn(new GenerateSongResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::SONG,
            name: new Name('テスト'),
            agencyIdentifier: null,
            groupIdentifiers: [$groupId1, $groupId2],
            talentIdentifiers: [],
        );

        $result = $service->generate($payload);

        $this->assertSame('Test', $result->alphabetName());
    }

    /**
     * 正常系: Song生成でTalentのWikiが見つからない場合、talentNameがnullであること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateSongWithTalentNotFound(): void
    {
        $talentId = new WikiIdentifier(StrTestHelper::generateUuid());

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($talentId)
            ->once()
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $responseJson = $this->createGeminiResponseJson([
            'alphabet_name' => 'Test',
        ]);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(fn (GenerateSongRequest $r) => $r->talentName() === null)
            ->andReturn(new GenerateSongResponse($this->createPsrResponse($responseJson)));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::SONG,
            name: new Name('テスト'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [$talentId],
        );

        $result = $service->generate($payload);

        $this->assertSame('Test', $result->alphabetName());
    }

    // ========================================================================
    // ResourceType 異常系
    // ========================================================================

    /**
     * 異常系: サポートされていないResourceTypeの場合、InvalidArgumentExceptionが発生すること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateThrowsExceptionForUnsupportedResourceType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported resource type: image');

        $service = $this->app->make(AutoWikiCreationServiceInterface::class);
        $payload = new AutoWikiCreationPayload(
            language: Language::JAPANESE,
            resourceType: ResourceType::IMAGE,
            name: new Name('テスト'),
            agencyIdentifier: null,
            groupIdentifiers: [],
            talentIdentifiers: [],
        );

        $service->generate($payload);
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    private function createWikiMock(string $name): Wiki|Mockery\MockInterface
    {
        $basic = Mockery::mock(BasicInterface::class);
        $basic->shouldReceive('name')
            ->andReturn(new Name($name));

        $wiki = Mockery::mock(Wiki::class);
        $wiki->shouldReceive('basic')
            ->andReturn($basic);

        return $wiki;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<array{uri: string, title: string}> $sources
     * @throws JsonException
     */
    private function createGeminiResponseJson(array $data, array $sources = []): string
    {
        $candidate = [
            'content' => [
                'parts' => [
                    [
                        'text' => json_encode($data, JSON_THROW_ON_ERROR),
                    ],
                ],
            ],
        ];

        if ($sources !== []) {
            $candidate['groundingMetadata'] = [
                'groundingChunks' => array_map(
                    static fn (array $s) => ['web' => $s],
                    $sources,
                ),
            ];
        }

        return json_encode([
            'candidates' => [$candidate],
        ], JSON_THROW_ON_ERROR);
    }

    private function createPsrResponse(string $body): ResponseInterface|Mockery\MockInterface
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->once()
            ->andReturn($body);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($stream);

        return $response;
    }
}
