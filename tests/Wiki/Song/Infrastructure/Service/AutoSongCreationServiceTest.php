<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongRequest;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier as AgencyDomainIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\Description as AgencyDescription;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\Description as GroupDescription;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier as GroupDomainIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier as TalentDomainIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Service\AutoSongCreationServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutoSongCreationPayload;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Infrastructure\Service\AutoSongCreationService;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutoSongCreationServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(AutoSongCreationServiceInterface::class);

        $this->assertInstanceOf(AutoSongCreationService::class, $service);
    }

    /**
     * 正常系: Dynamiteの情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateDynamite(): void
    {
        $overview = <<<'OVERVIEW'
Dynamite（ダイナマイト）は、BTSが2020年8月21日にリリースした楽曲です。グループ初の全編英語歌詞の曲で、Billboard Hot 100で1位を獲得し、K-POPグループとして初の快挙を達成しました。
OVERVIEW;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Dynamite',
                                    'lyricist' => 'David Stewart, Jessica Agombar',
                                    'composer' => 'David Stewart, Jessica Agombar',
                                    'release_date' => '2020-08-21',
                                    'overview' => $overview,
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://ja.wikipedia.org/wiki/Dynamite_(BTS%E3%81%AE%E6%9B%B2)',
                                    'title' => 'Dynamite (BTSの曲) - Wikipedia',
                                ],
                            ],
                            [
                                'web' => [
                                    'uri' => 'https://bts-official.jp/',
                                    'title' => 'BTS JAPAN OFFICIAL',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload('다이나마이트', Language::JAPANESE);

        $result = $service->generate($payload);

        $this->assertSame('Dynamite', $result->alphabetName());
        $this->assertSame('David Stewart, Jessica Agombar', $result->lyricist());
        $this->assertSame('David Stewart, Jessica Agombar', $result->composer());
        $this->assertSame('2020-08-21', $result->releaseDate());
        $this->assertStringContainsString('Billboard Hot 100', $result->overview());
        $this->assertCount(2, $result->sources());
        $this->assertStringContainsString('wikipedia.org', $result->sources()[0]->uri());
        $this->assertSame('Dynamite (BTSの曲) - Wikipedia', $result->sources()[0]->title());
    }

    /**
     * 異常系: GeminiExceptionが発生した場合、空のデータが返されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateReturnsEmptyDataOnGeminiException(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->andThrow(new GeminiException('Gemini API rate limit exceeded'));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload('Next Level', Language::KOREAN);

        $result = $service->generate($payload);

        $this->assertNull($result->alphabetName());
        $this->assertNull($result->lyricist());
        $this->assertNull($result->composer());
        $this->assertNull($result->releaseDate());
        $this->assertNull($result->overview());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: 部分的なデータが返される場合も正しく処理されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithPartialData(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Butter',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload('버터', Language::JAPANESE);

        $result = $service->generate($payload);

        $this->assertSame('Butter', $result->alphabetName());
        $this->assertNull($result->lyricist());
        $this->assertNull($result->composer());
        $this->assertNull($result->releaseDate());
        $this->assertNull($result->overview());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: 韓国語で曲情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateNextLevelInKorean(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $overview = <<<'OVERVIEW'
Next Level은 에스파가 2021년 5월 17일에 발매한 싱글입니다. SM엔터테인먼트 소속으로, 걸그룹 최초로 빌보드 200에 진입했습니다.
OVERVIEW;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Next Level',
                                    'lyricist' => 'Yoo Young-jin',
                                    'composer' => 'Adrian Mckinnon, Lydia Paek',
                                    'release_date' => '2021-05-17',
                                    'overview' => $overview,
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://www.smtown.com/',
                                    'title' => 'SMTOWN 공식 사이트',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload('넥스트 레벨', Language::KOREAN);

        $result = $service->generate($payload);

        $this->assertSame('Next Level', $result->alphabetName());
        $this->assertStringContainsString('SM엔터테인먼트', $result->overview());
        $this->assertCount(1, $result->sources());
        $this->assertSame('https://www.smtown.com/', $result->sources()[0]->uri());
    }

    /**
     * 正常系: 重複するソースURIが除外されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateDeduplicatesSources(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'LOVE DIVE',
                                    'overview' => 'LOVE DIVEはIVEの楽曲です。',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://www.ive-official.com/',
                                    'title' => 'IVE Official',
                                ],
                            ],
                            [
                                'web' => [
                                    'uri' => 'https://www.ive-official.com/',
                                    'title' => 'IVE 공식',
                                ],
                            ],
                            [
                                'web' => [
                                    'uri' => 'https://ja.wikipedia.org/wiki/LOVE_DIVE',
                                    'title' => 'LOVE DIVE - Wikipedia',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload('러브 다이브', Language::JAPANESE);

        $result = $service->generate($payload);

        $this->assertSame('LOVE DIVE', $result->alphabetName());
        $this->assertCount(2, $result->sources());
        $this->assertSame('https://www.ive-official.com/', $result->sources()[0]->uri());
        $this->assertSame('IVE Official', $result->sources()[0]->title());
        $this->assertStringContainsString('wikipedia.org', $result->sources()[1]->uri());
    }

    /**
     * 正常系: 英語で曲情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateInEnglish(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $overview = <<<'OVERVIEW'
How You Like That is a song by BLACKPINK, released on June 26, 2020. The song broke multiple YouTube records for the most-viewed music video premiere. It reached the top 40 in multiple countries and was a major hit worldwide.
OVERVIEW;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'How You Like That',
                                    'lyricist' => 'Teddy Park, Danny Chung',
                                    'composer' => 'Teddy Park, 24',
                                    'release_date' => '2020-06-26',
                                    'overview' => $overview,
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://www.blackpinkofficial.com/',
                                    'title' => 'BLACKPINK Official',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload('How You Like That', Language::ENGLISH);

        $result = $service->generate($payload);

        $this->assertSame('How You Like That', $result->alphabetName());
        $this->assertStringContainsString('BLACKPINK', $result->overview());
        $this->assertStringContainsString('YouTube', $result->overview());
        $this->assertCount(1, $result->sources());
    }

    /**
     * 正常系: AgencyIdentifierがある場合、AgencyNameがリクエストに含まれること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithAgencyName(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $agencyName = 'HYBE';

        $agency = new Agency(
            new AgencyDomainIdentifier($agencyId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('hybe'),
            Language::KOREAN,
            new Name($agencyName),
            'hybe',
            new CEO(''),
            '',
            null,
            new AgencyDescription(''),
            new Version(1),
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->andReturn($agency);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Dynamite',
                                    'overview' => 'Dynamite is a song by BTS with rights held by HYBE.',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(function (GenerateSongRequest $request) use ($agencyName) {
                return $request->agencyName() === $agencyName
                    && $request->groupName() === null
                    && $request->talentName() === null;
            })
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload(
            '다이나마이트',
            Language::JAPANESE,
            new AgencyIdentifier($agencyId),
        );

        $result = $service->generate($payload);

        $this->assertSame('Dynamite', $result->alphabetName());
    }

    /**
     * 正常系: AgencyIdentifierがnullの場合、AgencyNameがnullであること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithoutAgencyIdentifier(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Dynamite',
                                    'overview' => 'Dynamite is a K-pop song.',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(function (GenerateSongRequest $request) {
                return $request->agencyName() === null
                    && $request->groupName() === null
                    && $request->talentName() === null;
            })
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload('다이나마이트', Language::KOREAN);

        $result = $service->generate($payload);

        $this->assertSame('Dynamite', $result->alphabetName());
    }

    /**
     * 正常系: AgencyIdentifierがあるが、Agencyが見つからない場合、AgencyNameがnullであること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithAgencyNotFound(): void
    {
        $agencyId = StrTestHelper::generateUuid();

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Next Level',
                                    'overview' => 'Next Level is a K-pop song.',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(function (GenerateSongRequest $request) {
                return $request->agencyName() === null;
            })
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload(
            '넥스트 레벨',
            Language::KOREAN,
            new AgencyIdentifier($agencyId),
        );

        $result = $service->generate($payload);

        $this->assertSame('Next Level', $result->alphabetName());
    }

    /**
     * 正常系: GroupIdentifierがある場合、GroupNameがリクエストに含まれること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithGroupName(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupId = StrTestHelper::generateUuid();
        $groupName = 'BTS';

        $group = new Group(
            new GroupDomainIdentifier($groupId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('bts'),
            Language::KOREAN,
            new GroupName($groupName),
            'bts',
            null,
            new GroupDescription(''),
            new Version(1),
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->andReturn($group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findById');

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Dynamite',
                                    'overview' => 'Dynamite is a song performed by BTS.',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(function (GenerateSongRequest $request) use ($groupName) {
                return $request->agencyName() === null
                    && $request->groupName() === $groupName
                    && $request->talentName() === null;
            })
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload(
            '다이나마이트',
            Language::JAPANESE,
            null,
            new GroupIdentifier($groupId),
        );

        $result = $service->generate($payload);

        $this->assertSame('Dynamite', $result->alphabetName());
    }

    /**
     * 正常系: TalentIdentifierがある場合、TalentNameがリクエストに含まれること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithTalentName(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldNotReceive('findById');

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentId = StrTestHelper::generateUuid();
        $talentNameStr = 'Jimin';

        $talent = new Talent(
            new TalentDomainIdentifier($talentId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('jimin'),
            Language::KOREAN,
            new TalentName($talentNameStr),
            new RealName('Park Jimin'),
            null,
            [],
            null,
            new Career(''),
            new Version(1),
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($talent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Promise',
                                    'overview' => 'Promise is a song performed by Jimin.',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(function (GenerateSongRequest $request) use ($talentNameStr) {
                return $request->agencyName() === null
                    && $request->groupName() === null
                    && $request->talentName() === $talentNameStr;
            })
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload(
            '약속',
            Language::JAPANESE,
            null,
            null,
            new TalentIdentifier($talentId),
        );

        $result = $service->generate($payload);

        $this->assertSame('Promise', $result->alphabetName());
    }

    /**
     * 正常系: GroupとTalentの両方がある場合、両方がリクエストに含まれること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithGroupAndTalent(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupId = StrTestHelper::generateUuid();
        $groupName = 'BTS';

        $group = new Group(
            new GroupDomainIdentifier($groupId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('bts'),
            Language::KOREAN,
            new GroupName($groupName),
            'bts',
            null,
            new GroupDescription(''),
            new Version(1),
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->andReturn($group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentId = StrTestHelper::generateUuid();
        $talentNameStr = 'Jimin';

        $talent = new Talent(
            new TalentDomainIdentifier($talentId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('jimin'),
            Language::KOREAN,
            new TalentName($talentNameStr),
            new RealName('Park Jimin'),
            null,
            [],
            null,
            new Career(''),
            new Version(1),
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($talent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Filter',
                                    'overview' => 'Filter is a song performed by Jimin of BTS.',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(function (GenerateSongRequest $request) use ($groupName, $talentNameStr) {
                return $request->agencyName() === null
                    && $request->groupName() === $groupName
                    && $request->talentName() === $talentNameStr;
            })
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload(
            '필터',
            Language::JAPANESE,
            null,
            new GroupIdentifier($groupId),
            new TalentIdentifier($talentId),
        );

        $result = $service->generate($payload);

        $this->assertSame('Filter', $result->alphabetName());
    }

    /**
     * 正常系: Agency、Group、Talentの全てがある場合、全てがリクエストに含まれること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithAllAffiliations(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $agencyName = 'SM Entertainment';

        $agency = new Agency(
            new AgencyDomainIdentifier($agencyId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('sm-entertainment'),
            Language::KOREAN,
            new Name($agencyName),
            'sm entertainment',
            new CEO(''),
            '',
            null,
            new AgencyDescription(''),
            new Version(1),
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->andReturn($agency);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupId = StrTestHelper::generateUuid();
        $groupName = 'aespa';

        $group = new Group(
            new GroupDomainIdentifier($groupId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('aespa'),
            Language::KOREAN,
            new GroupName($groupName),
            'aespa',
            null,
            new GroupDescription(''),
            new Version(1),
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->andReturn($group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $talentId = StrTestHelper::generateUuid();
        $talentNameStr = 'Karina';

        $talent = new Talent(
            new TalentDomainIdentifier($talentId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('karina'),
            Language::KOREAN,
            new TalentName($talentNameStr),
            new RealName('Yu Jimin'),
            null,
            [],
            null,
            new Career(''),
            new Version(1),
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($talent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Next Level',
                                    'overview' => 'Next Level is a song performed by Karina of aespa affiliated with SM Entertainment.',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->withArgs(function (GenerateSongRequest $request) use ($agencyName, $groupName, $talentNameStr) {
                return $request->agencyName() === $agencyName
                    && $request->groupName() === $groupName
                    && $request->talentName() === $talentNameStr;
            })
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoSongCreationServiceInterface::class);
        $payload = $this->makePayload(
            '넥스트 레벨',
            Language::JAPANESE,
            new AgencyIdentifier($agencyId),
            new GroupIdentifier($groupId),
            new TalentIdentifier($talentId),
        );

        $result = $service->generate($payload);

        $this->assertSame('Next Level', $result->alphabetName());
    }

    private function makePayload(
        string $name,
        Language $language,
        ?AgencyIdentifier $agencyIdentifier = null,
        ?GroupIdentifier $groupIdentifier = null,
        ?TalentIdentifier $talentIdentifier = null,
    ): AutoSongCreationPayload {
        return new AutoSongCreationPayload(
            language: $language,
            name: new SongName($name),
            agencyIdentifier: $agencyIdentifier,
            groupIdentifier: $groupIdentifier,
            talentIdentifier: $talentIdentifier,
        );
    }

    private function createGeminiResponse(string $body): ResponseInterface|Mockery\MockInterface
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
