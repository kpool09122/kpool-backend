<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentResponse;
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
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier as GroupDomainIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Service\AutoTalentCreationServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutoTalentCreationPayload;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Infrastructure\Service\AutoTalentCreationService;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
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

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Jimin',
                                    'description' => 'Jimin is a member of BTS affiliated with HYBE.',
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
            ->withArgs(function (GenerateTalentRequest $request) use ($agencyName) {
                return $request->agencyName() === $agencyName;
            })
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = new AutoTalentCreationPayload(
            Language::JAPANESE,
            new TalentName('지민'),
            new AgencyIdentifier($agencyId),
            [],
        );

        $result = $service->generate($payload);

        $this->assertSame('Jimin', $result->alphabetName());
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
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Jimin',
                                    'description' => 'Jimin is a K-pop idol.',
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
            ->withArgs(function (GenerateTalentRequest $request) {
                return $request->agencyName() === null;
            })
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = new AutoTalentCreationPayload(
            Language::KOREAN,
            new TalentName('지민'),
            null,
            [],
        );

        $result = $service->generate($payload);

        $this->assertSame('Jimin', $result->alphabetName());
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
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Karina',
                                    'description' => 'Karina is a K-pop idol.',
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
            ->withArgs(function (GenerateTalentRequest $request) {
                return $request->agencyName() === null;
            })
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = new AutoTalentCreationPayload(
            Language::KOREAN,
            new TalentName('카리나'),
            new AgencyIdentifier($agencyId),
            [],
        );

        $result = $service->generate($payload);

        $this->assertSame('Karina', $result->alphabetName());
    }

    /**
     * 正常系: GroupIdentifiersがある場合、GroupNamesがリクエストに含まれること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithGroupNames(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldNotReceive('findById');

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $groupId1 = StrTestHelper::generateUuid();
        $groupId2 = StrTestHelper::generateUuid();
        $groupName1 = 'BTS';
        $groupName2 = 'TXT';

        $group1 = new Group(
            new GroupDomainIdentifier($groupId1),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('bts'),
            Language::KOREAN,
            new GroupName($groupName1),
            'bts',
            null,
            new GroupDescription(''),
            new Version(1),
        );

        $group2 = new Group(
            new GroupDomainIdentifier($groupId2),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('txt'),
            Language::KOREAN,
            new GroupName($groupName2),
            'txt',
            null,
            new GroupDescription(''),
            new Version(1),
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->andReturn([$group1, $group2]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Jimin',
                                    'description' => 'Jimin is a member of BTS.',
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
            ->withArgs(function (GenerateTalentRequest $request) use ($groupName1, $groupName2) {
                return $request->groupNames() === [$groupName1, $groupName2];
            })
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = new AutoTalentCreationPayload(
            Language::JAPANESE,
            new TalentName('지민'),
            null,
            [new GroupIdentifier($groupId1), new GroupIdentifier($groupId2)],
        );

        $result = $service->generate($payload);

        $this->assertSame('Jimin', $result->alphabetName());
    }

    /**
     * 正常系: AgencyとGroupの両方がある場合、両方がリクエストに含まれること.
     *
     * @throws JsonException
     * @throws BindingResolutionException
     */
    public function testGenerateWithAgencyAndGroupNames(): void
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
        $groupRepository->shouldReceive('findByIds')
            ->once()
            ->andReturn([$group]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $responseJson = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'alphabet_name' => 'Karina',
                                    'description' => 'Karina is a member of aespa affiliated with SM Entertainment.',
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
            ->withArgs(function (GenerateTalentRequest $request) use ($agencyName, $groupName) {
                return $request->agencyName() === $agencyName && $request->groupNames() === [$groupName];
            })
            ->andReturn(new GenerateTalentResponse($response));

        $this->app->instance(GeminiClient::class, $geminiClient);

        $service = $this->app->make(AutoTalentCreationServiceInterface::class);
        $payload = new AutoTalentCreationPayload(
            Language::JAPANESE,
            new TalentName('카리나'),
            new AgencyIdentifier($agencyId),
            [new GroupIdentifier($groupId)],
        );

        $result = $service->generate($payload);

        $this->assertSame('Karina', $result->alphabetName());
    }

    /**
     * @param GroupIdentifier[] $groupIdentifiers
     */
    private function makePayload(
        string $name,
        Language $language,
        ?AgencyIdentifier $agencyIdentifier = null,
        array $groupIdentifiers = [],
    ): AutoTalentCreationPayload {
        return new AutoTalentCreationPayload(
            language: $language,
            name: new TalentName($name),
            agencyIdentifier: $agencyIdentifier,
            groupIdentifiers: $groupIdentifiers,
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
