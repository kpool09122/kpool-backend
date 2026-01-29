<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\Service\AutomaticDraftSongCreationServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Infrastructure\Service\GeminiAutomaticDraftSongCreationService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GeminiAutomaticDraftSongCreationServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(AutomaticDraftSongCreationServiceInterface::class);

        $this->assertInstanceOf(GeminiAutomaticDraftSongCreationService::class, $service);
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

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->andReturn(new GenerateSongResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutomaticDraftSongCreationServiceInterface::class);
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
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateSong')
            ->once()
            ->andThrow(new GeminiException('Gemini API rate limit exceeded'));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutomaticDraftSongCreationServiceInterface::class);
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

        $service = $this->app->make(AutomaticDraftSongCreationServiceInterface::class);
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

        $service = $this->app->make(AutomaticDraftSongCreationServiceInterface::class);
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

        $service = $this->app->make(AutomaticDraftSongCreationServiceInterface::class);
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

        $service = $this->app->make(AutomaticDraftSongCreationServiceInterface::class);
        $payload = $this->makePayload('How You Like That', Language::ENGLISH);

        $result = $service->generate($payload);

        $this->assertSame('How You Like That', $result->alphabetName());
        $this->assertStringContainsString('BLACKPINK', $result->overview());
        $this->assertStringContainsString('YouTube', $result->overview());
        $this->assertCount(1, $result->sources());
    }

    private function makePayload(string $name, Language $language): AutomaticDraftSongCreationPayload
    {
        return new AutomaticDraftSongCreationPayload(
            language: $language,
            name: new SongName($name),
            agencyIdentifier: new AgencyIdentifier(StrTestHelper::generateUuid()),
            groupIdentifier: new GroupIdentifier(StrTestHelper::generateUuid()),
            talentIdentifier: new TalentIdentifier(StrTestHelper::generateUuid()),
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
