<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Service\TranslationServiceInterface;
use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgency;
use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateAgencyTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $agencyService = Mockery::mock(TranslationServiceInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $this->assertInstanceOf(TranslateAgency::class, $translateAgency);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.
**\'진실, 성실, 겸손\'**이라는 가치관을 매우 중시하며, 소속 아티스트의 노래나 댄스 실력뿐만 아니라 인성을 존중하는 육성 방침으로 알려져 있습니다. 이러한 철학은 박진영이 오디션 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다.
음악적인 면에서는 설립자인 박진영이 직접 프로듀서로서 많은 곡 작업에 참여하여, 대중에게 사랑받는 캐치한 히트곡을 수많이 만들어왔습니다.
---
### 주요 소속 아티스트
지금까지 **원더걸스(Wonder Girls)**, **2PM**, **미쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출해왔습니다.
현재도
* **트와이스 (TWICE)**
* **스트레이 키즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new TranslateAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $principal,
        );

        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
        );

        $japanese = Translation::JAPANESE;
        $jaName = new AgencyName('JYPエンターテインメント');
        $jaCEO = new CEO('J.Y. Park');
        $jaDescription = new Description('JYPエンターテインメント (JYP Entertainment)
歌手兼音楽プロデューサーである**パク・ジニョン（J.Y. Park）が1997年に設立した韓国の大手総合エンターテインメント企業です。HYBE、SM、YGエンターテインメントと共に、韓国の芸能界をリードする「BIG4」**の一つに数えられています。
**「真実、誠実、謙虚」**という価値観を非常に重視し、所属アーティストの歌やダンスの実力だけでなく、人柄を尊重する育成方針で知られています。このような哲学は、パク・ジニョンがオーディション番組などで見せる姿を通じても広く知られています。
音楽面では、設立者であるパク・ジニョン自らがプロデューサーとして多くの楽曲制作に参加し、大衆に愛されるキャッチーなヒット曲を数多く生み出してきました。
主な所属アーティスト
これまでWonder Girls、2PM、miss Aのように、K-POPの歴史を築いてきたグループを輩出してきました。
現在も
TWICE
Stray Kids
ITZY
NMIXX
など、世界的な人気を誇るグループが多数所属しており、K-POPのグローバルな発展において中心的な役割を担い続けています。音楽事業のほか、俳優のマネジメントや公演事業なども手掛けています。');
        $jaAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaCEO,
            $foundedIn,
            $jaDescription,
            ApprovalStatus::Pending
        );

        $english = Translation::ENGLISH;
        $enName = new AgencyName('JYP Entertainment');
        $enCEO = new CEO('J.Y. Park');
        $enDescription = new Description('JYP Entertainment
JYP Entertainment is a major South Korean multinational entertainment company founded in 1997 by singer and music producer J.Y. Park. It is considered one of the "BIG4" entertainment companies in South Korea, alongside HYBE, SM Entertainment, and YG Entertainment.
The company places great importance on the values of "Truth, Sincerity, and Humility," and is known for its policy of developing artists by emphasizing not only their singing and dancing skills but also their personal character. This philosophy has become widely known through J.Y. Park\'s appearances on audition programs.
    Musically, the founder J.Y. Park has been personally involved as a producer on many tracks, creating a multitude of catchy hit songs beloved by the public.
Major Artists
Throughout its history, the company has produced groups that have shaped the history of K-pop, such as Wonder Girls, 2PM, and miss A.
    Currently, it is home to many globally popular groups, including:
TWICE
Stray Kids
ITZY
NMIXX
These groups continue to play a central role in the global growth of K-pop. In addition to its music business, the company is also involved in actor management and the concert business.');
        $enAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enCEO,
            $foundedIn,
            $enDescription,
            ApprovalStatus::Pending
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($agencyIdentifier)
            ->once()
            ->andReturn($agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->with($enAgency)
            ->once()
            ->andReturn(null);
        $agencyRepository->shouldReceive('saveDraft')
            ->with($jaAgency)
            ->once()
            ->andReturn(null);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);
        $agencyService->shouldReceive('translateAgency')
            ->with($agency, $english)
            ->once()
            ->andReturn($enAgency);
        $agencyService->shouldReceive('translateAgency')
            ->with($agency, $japanese)
            ->once()
            ->andReturn($jaAgency);

        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $agencies = $translateAgency->process($input);
        $this->assertCount(2, $agencies);
        $this->assertSame($jaAgency, $agencies[0]);
        $this->assertSame($enAgency, $agencies[1]);
    }

    /**
     * 異常系： 指定したIDの事務所情報が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenAgencyNotFound(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new TranslateAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($agencyIdentifier)
            ->once()
            ->andReturn(null);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->expectException(AgencyNotFoundException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * 異常系：翻訳権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);

        $input = new TranslateAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $principal,
        );

        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($agencyIdentifier)
            ->once()
            ->andReturn($agency);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->expectException(UnauthorizedException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * 正常系：ADMINISTRATORが事務所を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcessWithAdministrator(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new TranslateAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $principal,
        );

        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
        );

        $japanese = Translation::JAPANESE;
        $jaName = new AgencyName('JYPエンターテインメント');
        $jaCEO = new CEO('J.Y. Park');
        $jaDescription = new Description('### JYPエンターテインメント (JYP Entertainment)
歌手兼音楽プロデューサーである**パク・ジニョン（J.Y. Park）**が1997年に設立した韓国の大手総合エンターテインメント企業です。HYBE、SM、YGエンターテインメントと共に、韓国の芸能界をリードする**「BIG4」**の一つに数えられています。');
        $jaAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaCEO,
            $foundedIn,
            $jaDescription,
            ApprovalStatus::Pending
        );

        $english = Translation::ENGLISH;
        $enName = new AgencyName('JYP Entertainment');
        $enCEO = new CEO('J.Y. Park');
        $enDescription = new Description('### JYP Entertainment
JYP Entertainment is a major South Korean multinational entertainment company founded in 1997 by singer and music producer **J.Y. Park**. It is considered one of the **"BIG4"** entertainment companies in South Korea, alongside HYBE, SM Entertainment, and YG Entertainment.');
        $enAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enCEO,
            $foundedIn,
            $enDescription,
            ApprovalStatus::Pending
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($agencyIdentifier)
            ->once()
            ->andReturn($agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->with($enAgency)
            ->once()
            ->andReturn(null);
        $agencyRepository->shouldReceive('saveDraft')
            ->with($jaAgency)
            ->once()
            ->andReturn(null);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);
        $agencyService->shouldReceive('translateAgency')
            ->with($agency, $english)
            ->once()
            ->andReturn($enAgency);
        $agencyService->shouldReceive('translateAgency')
            ->with($agency, $japanese)
            ->once()
            ->andReturn($jaAgency);

        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $result = $translateAgency->process($input);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(DraftAgency::class, $result[0]);
        $this->assertInstanceOf(DraftAgency::class, $result[1]);
    }

    /**
     * 異常系：GROUP_ACTORが事務所情報を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     */
    public function testUnauthorizedGroupActor(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$groupId], null);

        $input = new TranslateAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $principal,
        );

        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($agencyIdentifier)
            ->once()
            ->andReturn($agency);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->expectException(UnauthorizedException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * 異常系：TALENT_ACTORが事務所情報を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     */
    public function testUnauthorizedTalentActor(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$groupId], $talentId);

        $input = new TranslateAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $principal,
        );

        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($agencyIdentifier)
            ->once()
            ->andReturn($agency);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->expectException(UnauthorizedException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * 異常系：AGENCY_ACTORが他の事務所の事務所情報を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $anotherAgencyId, [], null);

        $input = new TranslateAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $principal,
        );

        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($agencyIdentifier)
            ->once()
            ->andReturn($agency);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->expectException(UnauthorizedException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の事務所の事務所情報を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $agencyIdentifier = new AgencyIdentifier($agencyId);
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], null);

        $input = new TranslateAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $principal,
        );

        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
        );

        $japanese = Translation::JAPANESE;
        $jaName = new AgencyName('JYPエンターテインメント');
        $jaCEO = new CEO('J.Y. Park');
        $jaDescription = new Description('### JYPエンターテインメント (JYP Entertainment)
歌手兼音楽プロデューサーである**パク・ジニョン（J.Y. Park）**が1997年に設立した韓国の大手総合エンターテインメント企業です。HYBE、SM、YGエンターテインメントと共に、韓国の芸能界をリードする**「BIG4」**の一つに数えられています。');
        $jaAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaCEO,
            $foundedIn,
            $jaDescription,
            ApprovalStatus::Pending
        );

        $english = Translation::ENGLISH;
        $enName = new AgencyName('JYP Entertainment');
        $enCEO = new CEO('J.Y. Park');
        $enDescription = new Description('### JYP Entertainment
JYP Entertainment is a major South Korean multinational entertainment company founded in 1997 by singer and music producer **J.Y. Park**. It is considered one of the **"BIG4"** entertainment companies in South Korea, alongside HYBE, SM Entertainment, and YG Entertainment.');
        $enAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enCEO,
            $foundedIn,
            $enDescription,
            ApprovalStatus::Pending
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($agencyIdentifier)
            ->once()
            ->andReturn($agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->with($enAgency)
            ->once()
            ->andReturn(null);
        $agencyRepository->shouldReceive('saveDraft')
            ->with($jaAgency)
            ->once()
            ->andReturn(null);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);
        $agencyService->shouldReceive('translateAgency')
            ->with($agency, $english)
            ->once()
            ->andReturn($enAgency);
        $agencyService->shouldReceive('translateAgency')
            ->with($agency, $japanese)
            ->once()
            ->andReturn($jaAgency);

        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $result = $translateAgency->process($input);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(DraftAgency::class, $result[0]);
        $this->assertInstanceOf(DraftAgency::class, $result[1]);
    }
}
