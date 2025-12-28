<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\SubmitAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency\SubmitAgency;
use Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency\SubmitAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency\SubmitAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\AgencyHistory;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyHistoryRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyHistoryIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitAgencyTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $submitAgency = $this->app->make(SubmitAgencyInterface::class);
        $this->assertInstanceOf(SubmitAgency::class, $submitAgency);
    }

    /**
     * 正常系：正しくAgency Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $dummySubmitAgency = $this->createDummySubmitAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitAgencyInput(
            $dummySubmitAgency->agencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitAgency->agency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitAgency->agencyIdentifier)
            ->andReturn($dummySubmitAgency->agency);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitAgency->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $submitAgency = $this->app->make(SubmitAgencyInterface::class);
        $agency = $submitAgency->process($input);
        $this->assertNotSame($dummySubmitAgency->status, $agency->status());
        $this->assertSame(ApprovalStatus::UnderReview, $agency->status());
    }

    /**
     * 異常系：指定したIDに紐づくAgencyが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundAgency(): void
    {
        $dummySubmitAgency = $this->createDummySubmitAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new SubmitAgencyInput(
            $dummySubmitAgency->agencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitAgency->agencyIdentifier)
            ->andReturn(null);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->expectException(AgencyNotFoundException::class);
        $submitAgency = $this->app->make(SubmitAgencyInterface::class);
        $submitAgency->process($input);
    }

    /**
     * 異常系：承認ステータスがPendingかRejected以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummySubmitAgency = $this->createDummySubmitAgency(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new SubmitAgencyInput(
            $dummySubmitAgency->agencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitAgency->agencyIdentifier)
            ->andReturn($dummySubmitAgency->agency);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->expectException(InvalidStatusException::class);
        $submitAgency = $this->app->make(SubmitAgencyInterface::class);
        $submitAgency->process($input);
    }

    /**
     * 正常系：COLLABORATORがAgencyを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::COLLABORATOR, null, [], []);

        $dummySubmitAgency = $this->createDummySubmitAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitAgencyInput($dummySubmitAgency->agencyIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitAgency->agencyIdentifier)
            ->andReturn($dummySubmitAgency->agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitAgency->agency)
            ->andReturn(null);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitAgency->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：AGENCY_ACTORがAgencyを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $dummySubmitAgency = $this->createDummySubmitAgency(
            agencyId: $agencyId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitAgencyInput($dummySubmitAgency->agencyIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitAgency->agencyIdentifier)
            ->andReturn($dummySubmitAgency->agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitAgency->agency)
            ->andReturn(null);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitAgency->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：GROUP_ACTORがAgencyを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithGroupActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::GROUP_ACTOR, null, [$groupId], []);

        $dummySubmitAgency = $this->createDummySubmitAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitAgencyInput($dummySubmitAgency->agencyIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitAgency->agencyIdentifier)
            ->andReturn($dummySubmitAgency->agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitAgency->agency)
            ->andReturn(null);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitAgency->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：TALENT_ACTORがAgencyを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithTalentActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::TALENT_ACTOR, null, [$groupId], [$talentId]);

        $dummySubmitAgency = $this->createDummySubmitAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitAgencyInput($dummySubmitAgency->agencyIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitAgency->agencyIdentifier)
            ->andReturn($dummySubmitAgency->agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitAgency->agency)
            ->andReturn(null);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitAgency->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：SENIOR_COLLABORATORがAgencyを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $dummySubmitAgency = $this->createDummySubmitAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitAgencyInput($dummySubmitAgency->agencyIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitAgency->agencyIdentifier)
            ->andReturn($dummySubmitAgency->agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitAgency->agency)
            ->andReturn(null);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitAgency->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 異常系：NONEロールがAgencyを提出しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummySubmitAgency = $this->createDummySubmitAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::NONE, null, [], []);

        $input = new SubmitAgencyInput($dummySubmitAgency->agencyIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitAgency->agencyIdentifier)
            ->andReturn($dummySubmitAgency->agency);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $useCase->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $agencyId
     * @param ApprovalStatus $status
     * @param EditorIdentifier|null $operatorIdentifier
     * @return SubmitAgencyTestData
     */
    private function createDummySubmitAgency(
        ?string $agencyId = null,
        ApprovalStatus $status = ApprovalStatus::Pending,
        ?EditorIdentifier $operatorIdentifier = null,
    ): SubmitAgencyTestData {
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description(<<<'DESC'
### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **'BIG4'** 중 하나로 꼽힙니다.
**'진실, 성실, 겸손'**이라는 가치관을 매우 중시하며, 소속 아티스트의 노래나 댄스 실력뿐만 아니라 인성을 존중하는 육성 방침으로 알려져 있습니다. 이러한 철학은 박진영이 오디션 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다.
음악적인 면에서는 설립자인 박진영이 직접 프로듀서로서 많은 곡 작업에 참여하여, 대중에게 사랑받는 캐치한 히트곡을 수많이 만들어왔습니다.
---
### 주요 소속 아티스트
지금까지 **원더걸스(Wonder Girls)**, **2PM**, **미쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출해왔습니다.
현재도
* **트와이스 (TWICE)**
* **스트레이 키즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.
DESC);

        $agency = new DraftAgency(
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

        $historyIdentifier = new AgencyHistoryIdentifier(StrTestHelper::generateUlid());
        $history = new AgencyHistory(
            $historyIdentifier,
            $operatorIdentifier ?? new EditorIdentifier(StrTestHelper::generateUlid()),
            $agency->editorIdentifier(),
            $agency->publishedAgencyIdentifier(),
            $agency->agencyIdentifier(),
            $status,
            ApprovalStatus::UnderReview,
            $agency->name(),
            new DateTimeImmutable('now'),
        );

        return new SubmitAgencyTestData(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $status,
            $agency,
            $historyIdentifier,
            $history,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class SubmitAgencyTestData
{
    public function __construct(
        public AgencyIdentifier $agencyIdentifier,
        public AgencyIdentifier $publishedAgencyIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public EditorIdentifier $editorIdentifier,
        public Language $language,
        public AgencyName $name,
        public CEO $CEO,
        public FoundedIn $foundedIn,
        public Description $description,
        public ApprovalStatus $status,
        public DraftAgency $agency,
        public AgencyHistoryIdentifier $historyIdentifier,
        public AgencyHistory $history,
    ) {
    }
}
