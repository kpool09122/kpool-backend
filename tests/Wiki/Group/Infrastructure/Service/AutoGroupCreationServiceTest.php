<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Domain\Service\AutoGroupCreationServiceInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AutoGroupCreationPayload;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Infrastructure\Service\AutoGroupCreationService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutoGroupCreationServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(AutoGroupCreationServiceInterface::class);

        $this->assertInstanceOf(AutoGroupCreationService::class, $service);
    }

    /**
     * 正常系: TWICE の情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateTwice(): void
    {
        $description = <<<'DESCRIPTION'
TWICEは、2015年のサバイバル番組「SIXTEEN」を通じて結成された、JYPエンターテインメント所属の韓国9人組ガールズグループです。メンバーは韓国人のナヨン、ジョンヨン、ジヒョ、ダヒョン、チェヨン、日本人のモモ、サナ、ミナ、台湾人のツウィで構成されています。「TT」「CHEER UP」「What is Love?」などのヒット曲で知られています。
DESCRIPTION;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'TWICE',
                                    'description' => $description,
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://www.twicejapan.com/',
                                    'title' => 'TWICE JAPAN OFFICIAL',
                                ],
                            ],
                            [
                                'web' => [
                                    'uri' => 'https://ja.wikipedia.org/wiki/TWICE',
                                    'title' => 'TWICE - Wikipedia',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->andReturn(new GenerateGroupResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoGroupCreationServiceInterface::class);
        $payload = $this->makePayload('트와이스', Language::JAPANESE);

        $result = $service->generate($payload);

        $this->assertSame('TWICE', $result->alphabetName());
        $this->assertStringContainsString('JYPエンターテインメント', $result->description());
        $this->assertCount(2, $result->sources());
        $this->assertSame('https://www.twicejapan.com/', $result->sources()[0]->uri());
        $this->assertSame('TWICE JAPAN OFFICIAL', $result->sources()[0]->title());
    }

    /**
     * 異常系: GeminiExceptionが発生した場合、空のデータが返されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateReturnsEmptyDataOnGeminiException(): void
    {
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->andThrow(new GeminiException('Gemini API rate limit exceeded'));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoGroupCreationServiceInterface::class);
        $payload = $this->makePayload('BTS', Language::KOREAN);

        $result = $service->generate($payload);

        $this->assertNull($result->alphabetName());
        $this->assertNull($result->description());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: BLACKPINKの部分的なデータが返される場合も正しく処理されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateBlackpinkWithPartialData(): void
    {
        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'BLACKPINK',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->andReturn(new GenerateGroupResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoGroupCreationServiceInterface::class);
        $payload = $this->makePayload('블랙핑크', Language::JAPANESE);

        $result = $service->generate($payload);

        $this->assertSame('BLACKPINK', $result->alphabetName());
        $this->assertNull($result->description());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: 韓国語で aespa の情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateAespaInKorean(): void
    {
        $description = <<<'DESCRIPTION'
에스파(aespa)는 2020년 SM엔터테인먼트에서 데뷔한 4인조 걸그룹입니다. 멤버는 카리나, 지젤, 윈터, 닝닝으로 구성되어 있으며, 메타버스 세계관을 도입한 독특한 컨셉으로 주목받고 있습니다.
DESCRIPTION;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'aespa',
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
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->andReturn(new GenerateGroupResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoGroupCreationServiceInterface::class);
        $payload = $this->makePayload('에스파', Language::KOREAN);

        $result = $service->generate($payload);

        $this->assertSame('aespa', $result->alphabetName());
        $this->assertStringContainsString('SM엔터테인먼트', $result->description());
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
                                    'alphabet_name' => 'NewJeans',
                                    'description' => 'NewJeansは2022年にHYBE傘下のADORからデビューした5人組ガールズグループです。',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://www.newjeans.kr/',
                                    'title' => 'NewJeans Official',
                                ],
                            ],
                            [
                                'web' => [
                                    'uri' => 'https://www.newjeans.kr/',
                                    'title' => 'NewJeans 공식',
                                ],
                            ],
                            [
                                'web' => [
                                    'uri' => 'https://ja.wikipedia.org/wiki/NewJeans',
                                    'title' => 'NewJeans - Wikipedia',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->andReturn(new GenerateGroupResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoGroupCreationServiceInterface::class);
        $payload = $this->makePayload('뉴진스', Language::JAPANESE);

        $result = $service->generate($payload);

        $this->assertSame('NewJeans', $result->alphabetName());
        $this->assertCount(2, $result->sources());
        $this->assertSame('https://www.newjeans.kr/', $result->sources()[0]->uri());
        $this->assertSame('NewJeans Official', $result->sources()[0]->title());
        $this->assertStringContainsString('wikipedia.org', $result->sources()[1]->uri());
    }

    /**
     * 正常系: 英語でIVEの情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateIveInEnglish(): void
    {
        $description = <<<'DESCRIPTION'
IVE is a South Korean girl group formed by Starship Entertainment in 2021. The group consists of six members: Yujin, Gaeul, Rei, Wonyoung, Liz, and Leeseo. Known for hits like "ELEVEN" and "Love Dive", they have become one of the leading fourth-generation K-pop groups.
DESCRIPTION;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'IVE',
                                    'description' => $description,
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
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateGroup')
            ->once()
            ->andReturn(new GenerateGroupResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoGroupCreationServiceInterface::class);
        $payload = $this->makePayload('IVE', Language::ENGLISH);

        $result = $service->generate($payload);

        $this->assertSame('IVE', $result->alphabetName());
        $this->assertStringContainsString('Starship Entertainment', $result->description());
        $this->assertStringContainsString('ELEVEN', $result->description());
        $this->assertCount(1, $result->sources());
    }

    private function makePayload(string $name, Language $language): AutoGroupCreationPayload
    {
        return new AutoGroupCreationPayload(
            $language,
            new GroupName($name),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
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
