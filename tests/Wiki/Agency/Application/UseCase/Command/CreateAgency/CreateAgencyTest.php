<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\UseCase\Command\CreateAgency\CreateAgency;
use Source\Wiki\Agency\Application\UseCase\Command\CreateAgency\CreateAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\CreateAgency\CreateAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateAgencyTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        // TODO: 各実装クラス作ったら削除する
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $this->assertInstanceOf(CreateAgency::class, $createAgency);
    }

    /**
     * 正常系：正しくAgency Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $dummyCreateAgency = $this->createDummyCreateAgencyData();

        $input = new CreateAgencyInput(
            $dummyCreateAgency->publishedAgencyIdentifier,
            $dummyCreateAgency->editorIdentifier,
            $dummyCreateAgency->language,
            $dummyCreateAgency->name,
            $dummyCreateAgency->CEO,
            $dummyCreateAgency->foundedIn,
            $dummyCreateAgency->description,
            $dummyCreateAgency->principalIdentifier,
        );

        [$agencyFactory, $agencyRepository] = $this->mockAgencyFactoryAndRepository(
            $dummyCreateAgency,
            $dummyCreateAgency->publishedAgency,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummyCreateAgency->principalIdentifier)
            ->once()
            ->andReturn($dummyCreateAgency->principal);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $agency = $createAgency->process($input);

        $this->assertTrue(UlidValidator::isValid((string)$agency->agencyIdentifier()));
        $this->assertSame((string)$dummyCreateAgency->publishedAgencyIdentifier, (string)$agency->publishedAgencyIdentifier());
        $this->assertSame((string)$dummyCreateAgency->editorIdentifier, (string)$agency->editorIdentifier());
        $this->assertSame($dummyCreateAgency->language->value, $agency->language()->value);
        $this->assertSame((string)$dummyCreateAgency->name, (string)$agency->name());
        $this->assertSame($dummyCreateAgency->normalizedName, $agency->normalizedName());
        $this->assertSame((string)$dummyCreateAgency->CEO, (string)$agency->CEO());
        $this->assertSame($dummyCreateAgency->normalizedCEO, $agency->normalizedCEO());
        $this->assertSame($dummyCreateAgency->foundedIn->value(), $agency->foundedIn()->value());
        $this->assertSame((string)$dummyCreateAgency->description, (string)$agency->description());
        $this->assertSame($dummyCreateAgency->status, $agency->status());
    }

    /**
     * 正常系：COLLABORATORがAgencyを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $dummyCreateAgency = $this->createDummyCreateAgencyData(Role::COLLABORATOR);

        $input = new CreateAgencyInput(
            $dummyCreateAgency->publishedAgencyIdentifier,
            $dummyCreateAgency->editorIdentifier,
            $dummyCreateAgency->language,
            $dummyCreateAgency->name,
            $dummyCreateAgency->CEO,
            $dummyCreateAgency->foundedIn,
            $dummyCreateAgency->description,
            $dummyCreateAgency->principalIdentifier,
        );

        [$agencyFactory, $agencyRepository] = $this->mockAgencyFactoryAndRepository(
            $dummyCreateAgency,
            null,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummyCreateAgency->principalIdentifier)
            ->once()
            ->andReturn($dummyCreateAgency->principal);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $createAgency->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORがAgencyを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $dummyCreateAgency = $this->createDummyCreateAgencyData(Role::AGENCY_ACTOR, $agencyId);

        $input = new CreateAgencyInput(
            $dummyCreateAgency->publishedAgencyIdentifier,
            $dummyCreateAgency->editorIdentifier,
            $dummyCreateAgency->language,
            $dummyCreateAgency->name,
            $dummyCreateAgency->CEO,
            $dummyCreateAgency->foundedIn,
            $dummyCreateAgency->description,
            $dummyCreateAgency->principalIdentifier,
        );

        [$agencyFactory, $agencyRepository] = $this->mockAgencyFactoryAndRepository(
            $dummyCreateAgency,
            null,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummyCreateAgency->principalIdentifier)
            ->once()
            ->andReturn($dummyCreateAgency->principal);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $createAgency->process($input);
    }

    /**
     * 正常系：GROUP_ACTORがAgencyを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithGroupActor(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $dummyCreateAgency = $this->createDummyCreateAgencyData(Role::GROUP_ACTOR, null, [$groupId]);

        $input = new CreateAgencyInput(
            $dummyCreateAgency->publishedAgencyIdentifier,
            $dummyCreateAgency->editorIdentifier,
            $dummyCreateAgency->language,
            $dummyCreateAgency->name,
            $dummyCreateAgency->CEO,
            $dummyCreateAgency->foundedIn,
            $dummyCreateAgency->description,
            $dummyCreateAgency->principalIdentifier,
        );

        [$agencyFactory, $agencyRepository] = $this->mockAgencyFactoryAndRepository(
            $dummyCreateAgency,
            null,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummyCreateAgency->principalIdentifier)
            ->once()
            ->andReturn($dummyCreateAgency->principal);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $createAgency->process($input);
    }

    /**
     * 正常系：TALENT_ACTORがAgencyを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithTalentActor(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();
        $dummyCreateAgency = $this->createDummyCreateAgencyData(Role::TALENT_ACTOR, null, [$groupId], [$talentId]);

        $input = new CreateAgencyInput(
            $dummyCreateAgency->publishedAgencyIdentifier,
            $dummyCreateAgency->editorIdentifier,
            $dummyCreateAgency->language,
            $dummyCreateAgency->name,
            $dummyCreateAgency->CEO,
            $dummyCreateAgency->foundedIn,
            $dummyCreateAgency->description,
            $dummyCreateAgency->principalIdentifier,
        );

        [$agencyFactory, $agencyRepository] = $this->mockAgencyFactoryAndRepository(
            $dummyCreateAgency,
            null,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummyCreateAgency->principalIdentifier)
            ->once()
            ->andReturn($dummyCreateAgency->principal);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $createAgency->process($input);
    }

    /**
     * 正常系：ADMINISTRATORがAgencyを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $dummyCreateAgency = $this->createDummyCreateAgencyData(Role::ADMINISTRATOR);

        $input = new CreateAgencyInput(
            $dummyCreateAgency->publishedAgencyIdentifier,
            $dummyCreateAgency->editorIdentifier,
            $dummyCreateAgency->language,
            $dummyCreateAgency->name,
            $dummyCreateAgency->CEO,
            $dummyCreateAgency->foundedIn,
            $dummyCreateAgency->description,
            $dummyCreateAgency->principalIdentifier,
        );

        [$agencyFactory, $agencyRepository] = $this->mockAgencyFactoryAndRepository(
            $dummyCreateAgency,
            null,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummyCreateAgency->principalIdentifier)
            ->once()
            ->andReturn($dummyCreateAgency->principal);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $createAgency->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがAgencyを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyCreateAgency = $this->createDummyCreateAgencyData(Role::SENIOR_COLLABORATOR);

        $input = new CreateAgencyInput(
            $dummyCreateAgency->publishedAgencyIdentifier,
            $dummyCreateAgency->editorIdentifier,
            $dummyCreateAgency->language,
            $dummyCreateAgency->name,
            $dummyCreateAgency->CEO,
            $dummyCreateAgency->foundedIn,
            $dummyCreateAgency->description,
            $dummyCreateAgency->principalIdentifier,
        );

        [$agencyFactory, $agencyRepository] = $this->mockAgencyFactoryAndRepository(
            $dummyCreateAgency,
            null,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummyCreateAgency->principalIdentifier)
            ->once()
            ->andReturn($dummyCreateAgency->principal);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $createAgency->process($input);
    }

    /**
     * 異常系：NONEロールがAgencyを作成しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummyCreateAgency = $this->createDummyCreateAgencyData(Role::NONE);

        $input = new CreateAgencyInput(
            $dummyCreateAgency->publishedAgencyIdentifier,
            $dummyCreateAgency->editorIdentifier,
            $dummyCreateAgency->language,
            $dummyCreateAgency->name,
            $dummyCreateAgency->CEO,
            $dummyCreateAgency->foundedIn,
            $dummyCreateAgency->description,
            $dummyCreateAgency->principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummyCreateAgency->principalIdentifier)
            ->once()
            ->andReturn($dummyCreateAgency->principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);

        $this->expectException(UnauthorizedException::class);
        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $createAgency->process($input);
    }

    /**
     * @param CreateAgencyTestData $dummy
     * @param Agency|null $existingAgency
     * @return array<int, Mockery\MockInterface>
     */
    private function mockAgencyFactoryAndRepository(CreateAgencyTestData $dummy, ?Agency $existingAgency): array
    {
        $agencyFactory = Mockery::mock(DraftAgencyFactoryInterface::class);
        $agencyFactory->shouldReceive('create')
            ->once()
            ->with($dummy->editorIdentifier, $dummy->language, $dummy->name)
            ->andReturn($dummy->draftAgency);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummy->publishedAgencyIdentifier)
            ->andReturn($existingAgency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummy->draftAgency)
            ->andReturn(null);

        return [$agencyFactory, $agencyRepository];
    }

    /**
     * @param Role $role
     * @param string|null $agencyScopeId
     * @param string[] $groupIds
     * @param string[] $talentIds
     * @return CreateAgencyTestData
     */
    private function createDummyCreateAgencyData(
        Role $role = Role::ADMINISTRATOR,
        ?string $agencyScopeId = null,
        array $groupIds = [],
        array $talentIds = [],
    ): CreateAgencyTestData {
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테インメント');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description(<<<'DESC'
### JYP엔터テインメント (JYP Entertainment)
가수 겸 음악 프로デューサー인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테インメント 기업입니다。HYBE, SM, YG엔터테インメント와 함께 한국 연예계를 이끄는 **'BIG4'** 중 하나로 꼽힙니다。
**'진실, 성실, 겸손'**이라는 가치관을 매우 중시하며、소속 아ーティストの노래やダンス 실력뿐만 아니라 인성을 존重する育成方針으로 알려져 있습니다。 이러한 철학은 박진영が オーディション 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다。
음악적인 면では 설립자인 박진영이 직접 プロデューサー로서 많은 곡 작업에 참여하여、대중에게 사랑받는キャッチ한ヒット곡を数多く 만들어왔습니다。
---
### 주요 소속 아ーティスト
지금まで **원더걸즈(Wonder Girls)**、**2PM**、**ミ쓰에이(Miss A)**と 같이 K팝の 역사를 만들어 온 그룹들을 배출해왔습니다。
현재도
* **트와이스 (TWICE)**
* **스트레이 キ즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹ス (NMIXX)**
등 세계적인 인기를 자랑하는 그룹가 多数 所属되어 있으며、K팝의 グローバル한 발전에서 중심적인 역할を 계속해서 맡고 있습니다。音楽 사업 외に 배우 マネジメントや 공연 事業도 하고 있습니다。
DESC);

        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $normalizedName = 'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ';
        $normalizedCEO = 'j.y. park';
        $status = ApprovalStatus::Pending;
        $draftAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
            $description,
            $status,
        );

        $version = new Version(1);
        $publishedAgency = new Agency(
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
            $description,
            $version,
        );

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), $role, $agencyScopeId, $groupIds, $talentIds);

        return new CreateAgencyTestData(
            $publishedAgencyIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
            $description,
            $principalIdentifier,
            $principal,
            $agencyIdentifier,
            $translationSetIdentifier,
            $status,
            $draftAgency,
            $publishedAgency,
            $version,
        );
    }
}

readonly class CreateAgencyTestData
{
    public function __construct(
        public AgencyIdentifier $publishedAgencyIdentifier,
        public EditorIdentifier $editorIdentifier,
        public Language $language,
        public AgencyName $name,
        public string $normalizedName,
        public CEO $CEO,
        public string $normalizedCEO,
        public FoundedIn $foundedIn,
        public Description $description,
        public PrincipalIdentifier $principalIdentifier,
        public Principal $principal,
        public AgencyIdentifier $agencyIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public ApprovalStatus $status,
        public DraftAgency $draftAgency,
        public Agency $publishedAgency,
        public Version $version,
    ) {
    }
}
