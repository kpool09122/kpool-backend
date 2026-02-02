<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Domain\Service\AutoAgencyCreationServiceInterface;
use Source\Wiki\Agency\Domain\ValueObject\AutoAgencyCreationPayload;
use Source\Wiki\Agency\Infrastructure\Service\AutoAgencyCreationService;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Tests\TestCase;

class AutoAgencyCreationServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(AutoAgencyCreationServiceInterface::class);

        $this->assertInstanceOf(AutoAgencyCreationService::class, $service);
    }

    /**
     * 正常系: JYP Entertainment の情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateJypEntertainment(): void
    {
        $description = <<<'DESCRIPTION'
JYP Entertainment（JYP엔터테인먼트）は、歌手・音楽プロデューサーのパク・ジニョン（J.Y. Park）が1997年に設立した韓国の大手総合エンターテインメント企業です。HYBE、SM、YGエンターテインメントと共に韓国芸能界を牽引する「BIG4」の一角を占めています。
「真実、誠実、謙遜」という価値観を非常に重視し、所属アーティストの歌やダンスの実力だけでなく人格を尊重する育成方針で知られています。
DESCRIPTION;

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
                            [
                                'web' => [
                                    'uri' => 'https://ja.wikipedia.org/wiki/JYP%E3%82%A8%E3%83%B3%E3%82%BF%E3%83%86%E3%82%A4%E3%83%B3%E3%83%A1%E3%83%B3%E3%83%88',
                                    'title' => 'JYPエンタテインメント - Wikipedia',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateAgency')
            ->once()
            ->andReturn(new GenerateAgencyResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoAgencyCreationServiceInterface::class);
        $payload = new AutoAgencyCreationPayload(
            language: Language::JAPANESE,
            name: new Name('JYP엔터테인먼트'),
        );

        $result = $service->generate($payload);

        $this->assertSame('JYP Entertainment', $result->alphabetName());
        $this->assertSame('J.Y. Park', $result->ceoName());
        $this->assertSame(1997, $result->foundedYear());
        $this->assertStringContainsString('パク・ジニョン', $result->description());
        $this->assertCount(2, $result->sources());
        $this->assertSame('https://www.jypentertainment.com/', $result->sources()[0]->uri());
        $this->assertSame('JYP Entertainment 公式サイト', $result->sources()[0]->title());
    }

    /**
     * 異常系: GeminiExceptionが発生した場合、空のデータが返されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateReturnsEmptyDataOnGeminiException(): void
    {
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateAgency')
            ->once()
            ->andThrow(new GeminiException('Gemini API rate limit exceeded'));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoAgencyCreationServiceInterface::class);
        $payload = new AutoAgencyCreationPayload(
            language: Language::KOREAN,
            name: new Name('SM엔터테인먼트'),
        );

        $result = $service->generate($payload);

        $this->assertNull($result->alphabetName());
        $this->assertNull($result->ceoName());
        $this->assertNull($result->foundedYear());
        $this->assertNull($result->description());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: HYBE の部分的なデータが返される場合も正しく処理されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateHybeWithPartialData(): void
    {
        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'HYBE',
                                    'description' => 'HYBEは、音楽プロデューサーのパン・シヒョクが2005年に設立した韓国の大手エンターテインメント企業です。BTSの世界的な成功を基盤に2021年に現在の社名に変更しました。',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateAgency')
            ->once()
            ->andReturn(new GenerateAgencyResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoAgencyCreationServiceInterface::class);
        $payload = new AutoAgencyCreationPayload(
            language: Language::JAPANESE,
            name: new Name('HYBE'),
        );

        $result = $service->generate($payload);

        $this->assertSame('HYBE', $result->alphabetName());
        $this->assertNull($result->ceoName());
        $this->assertNull($result->foundedYear());
        $this->assertStringContainsString('パン・シヒョク', $result->description());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: 韓国語で SM Entertainment の情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateSmEntertainmentInKorean(): void
    {
        $description = <<<'DESCRIPTION'
SM엔터테인먼트는 1995년 이수만이 설립한 대한민국의 종합 엔터테인먼트 기업입니다. H.O.T., S.E.S., 동방신기, 슈퍼주니어, 소녀시대, 샤이니, EXO, Red Velvet, NCT, aespa 등 수많은 아이돌 그룹을 배출하여 K-pop의 글로벌화를 이끌어 왔습니다.
DESCRIPTION;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'SM Entertainment',
                                    'ceo_name' => '이수만',
                                    'founded_year' => 1995,
                                    'description' => $description,
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
        $geminiClient->shouldReceive('generateAgency')
            ->once()
            ->andReturn(new GenerateAgencyResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoAgencyCreationServiceInterface::class);
        $payload = new AutoAgencyCreationPayload(
            language: Language::KOREAN,
            name: new Name('SM엔터테인먼트'),
        );

        $result = $service->generate($payload);

        $this->assertSame('SM Entertainment', $result->alphabetName());
        $this->assertSame('이수만', $result->ceoName());
        $this->assertSame(1995, $result->foundedYear());
        $this->assertStringContainsString('이수만', $result->description());
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
                                    'alphabet_name' => 'YG Entertainment',
                                    'ceo_name' => 'ヤン・ヒョンソク',
                                    'founded_year' => 1996,
                                    'description' => 'YGエンターテインメントは、BIGBANG、2NE1、BLACKPINKなどを輩出した韓国の大手芸能事務所です。',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://www.ygfamily.com/',
                                    'title' => 'YG Entertainment 公式サイト',
                                ],
                            ],
                            [
                                'web' => [
                                    'uri' => 'https://www.ygfamily.com/',
                                    'title' => 'YG Family Official',
                                ],
                            ],
                            [
                                'web' => [
                                    'uri' => 'https://ja.wikipedia.org/wiki/YG%E3%82%A8%E3%83%B3%E3%82%BF%E3%83%86%E3%82%A4%E3%83%B3%E3%83%A1%E3%83%B3%E3%83%88',
                                    'title' => 'YGエンタテインメント - Wikipedia',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateAgency')
            ->once()
            ->andReturn(new GenerateAgencyResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoAgencyCreationServiceInterface::class);
        $payload = new AutoAgencyCreationPayload(
            language: Language::JAPANESE,
            name: new Name('YGエンターテインメント'),
        );

        $result = $service->generate($payload);

        $this->assertSame('YG Entertainment', $result->alphabetName());
        $this->assertCount(2, $result->sources());
        $this->assertSame('https://www.ygfamily.com/', $result->sources()[0]->uri());
        $this->assertSame('YG Entertainment 公式サイト', $result->sources()[0]->title());
        $this->assertStringContainsString('wikipedia.org', $result->sources()[1]->uri());
    }

    /**
     * 正常系: 英語でStarship Entertainmentの情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateStarshipEntertainmentInEnglish(): void
    {
        $description = <<<'DESCRIPTION'
Starship Entertainment is a South Korean entertainment company established in 2008. The company is known for managing popular K-pop groups such as MONSTA X, IVE, and CRAVITY. It is a subsidiary of Kakao Entertainment.
DESCRIPTION;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Starship Entertainment',
                                    'ceo_name' => 'Kim Si-dae',
                                    'founded_year' => 2008,
                                    'description' => $description,
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://www.starship-ent.com/',
                                    'title' => 'Starship Entertainment Official',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateAgency')
            ->once()
            ->andReturn(new GenerateAgencyResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoAgencyCreationServiceInterface::class);
        $payload = new AutoAgencyCreationPayload(
            language: Language::ENGLISH,
            name: new Name('Starship Entertainment'),
        );

        $result = $service->generate($payload);

        $this->assertSame('Starship Entertainment', $result->alphabetName());
        $this->assertSame('Kim Si-dae', $result->ceoName());
        $this->assertSame(2008, $result->foundedYear());
        $this->assertStringContainsString('MONSTA X', $result->description());
        $this->assertStringContainsString('IVE', $result->description());
        $this->assertCount(1, $result->sources());
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
