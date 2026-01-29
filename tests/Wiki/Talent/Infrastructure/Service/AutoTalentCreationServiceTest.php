<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Talent\Domain\Service\AutoTalentCreationServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutoTalentCreationPayload;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Infrastructure\Service\AutoTalentCreationService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutoTalentCreationServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(AutoTalentCreationServiceInterface::class);

        $this->assertInstanceOf(AutoTalentCreationService::class, $service);
    }

    /**
     * 正常系: ジミンの情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateJimin(): void
    {
        $description = <<<'DESCRIPTION'
ジミン(本名: パク・ジミン)は、1995年10月13日生まれの韓国のアイドル歌手・ダンサーです。BTSのメンバーとして2013年にデビュー。グループのリードボーカル・メインダンサーを担当しています。ソロ曲「Lie」「Serendipity」「Filter」などで知られています。
DESCRIPTION;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Jimin',
                                    'real_name' => '박지민',
                                    'birthday' => '1995-10-13',
                                    'description' => $description,
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [
                            [
                                'web' => [
                                    'uri' => 'https://ja.wikipedia.org/wiki/ジミン_(BTS)',
                                    'title' => 'ジミン - Wikipedia',
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
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = $this->makePayload('지민', Language::JAPANESE);

        $result = $service->generate($payload);

        $this->assertSame('Jimin', $result->alphabetName());
        $this->assertSame('박지민', $result->realName());
        $this->assertSame('1995-10-13', $result->birthday());
        $this->assertStringContainsString('パク・ジミン', $result->description());
        $this->assertCount(2, $result->sources());
        $this->assertSame('https://ja.wikipedia.org/wiki/ジミン_(BTS)', $result->sources()[0]->uri());
        $this->assertSame('ジミン - Wikipedia', $result->sources()[0]->title());
    }

    /**
     * 異常系: GeminiExceptionが発生した場合、空のデータが返されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateReturnsEmptyDataOnGeminiException(): void
    {
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->andThrow(new GeminiException('Gemini API rate limit exceeded'));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = $this->makePayload('카리나', Language::KOREAN);

        $result = $service->generate($payload);

        $this->assertNull($result->alphabetName());
        $this->assertNull($result->realName());
        $this->assertNull($result->birthday());
        $this->assertNull($result->description());
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
                                    'alphabet_name' => 'Lisa',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = $this->makePayload('리사', Language::JAPANESE);

        $result = $service->generate($payload);

        $this->assertSame('Lisa', $result->alphabetName());
        $this->assertNull($result->realName());
        $this->assertNull($result->birthday());
        $this->assertNull($result->description());
        $this->assertEmpty($result->sources());
    }

    /**
     * 正常系: 韓国語でタレント情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateKarinaInKorean(): void
    {
        $description = <<<'DESCRIPTION'
카리나(본명: 유지민)는 2000년 4월 11일생의 한국 아이돌 가수입니다. 에스파의 리더이자 메인 래퍼, 리드 댄서를 담당하고 있습니다. 2020년 SM엔터테인먼트를 통해 데뷔했습니다.
DESCRIPTION;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Karina',
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
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = $this->makePayload('카리나', Language::KOREAN);

        $result = $service->generate($payload);

        $this->assertSame('Karina', $result->alphabetName());
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
                                    'alphabet_name' => 'Wonyoung',
                                    'description' => 'ウォニョンはIVEのメンバーです。',
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
                                    'uri' => 'https://ja.wikipedia.org/wiki/IVE',
                                    'title' => 'IVE - Wikipedia',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createGeminiResponse($responseJson);

        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = $this->makePayload('원영', Language::JAPANESE);

        $result = $service->generate($payload);

        $this->assertSame('Wonyoung', $result->alphabetName());
        $this->assertCount(2, $result->sources());
        $this->assertSame('https://www.ive-official.com/', $result->sources()[0]->uri());
        $this->assertSame('IVE Official', $result->sources()[0]->title());
        $this->assertStringContainsString('wikipedia.org', $result->sources()[1]->uri());
    }

    /**
     * 正常系: 英語でタレント情報が正しく生成されること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateInEnglish(): void
    {
        $description = <<<'DESCRIPTION'
Wonyoung (born August 31, 2004) is a South Korean singer. She is a member of IVE and was previously a member of IZ*ONE. She won first place in the survival show "Produce 48" in 2018 and debuted with IZ*ONE. After IZ*ONE's disbandment, she debuted with IVE in 2021.
DESCRIPTION;

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Wonyoung',
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
        $geminiClient->shouldReceive('generateTalent')
            ->once()
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = $this->makePayload('Wonyoung', Language::ENGLISH);

        $result = $service->generate($payload);

        $this->assertSame('Wonyoung', $result->alphabetName());
        $this->assertStringContainsString('IVE', $result->description());
        $this->assertStringContainsString('IZ*ONE', $result->description());
        $this->assertCount(1, $result->sources());
    }

    private function makePayload(string $name, Language $language): AutoTalentCreationPayload
    {
        return new AutoTalentCreationPayload(
            language: $language,
            name: new TalentName($name),
            agencyIdentifier: new AgencyIdentifier(StrTestHelper::generateUuid()),
            groupIdentifiers: [],
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
