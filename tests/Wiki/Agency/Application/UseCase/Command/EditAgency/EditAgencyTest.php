<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\EditAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\UseCase\Command\EditAgency\EditAgency;
use Source\Wiki\Agency\Application\UseCase\Command\EditAgency\EditAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\EditAgency\EditAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
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
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class EditAgencyTest extends TestCase
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
        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $editAgency = $this->app->make(EditAgencyInterface::class);
        $this->assertInstanceOf(EditAgency::class, $editAgency);
    }

    /**
     * 正常系：正しくAgency Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $dummyAgency = $this->createDummyEditAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new EditAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyAgency->agency)
            ->andReturn(null);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $editAgency = $this->app->make(EditAgencyInterface::class);
        $agency = $editAgency->process($input);
        $this->assertSame((string)$dummyAgency->agencyIdentifier, (string)$agency->agencyIdentifier());
        $this->assertSame((string)$dummyAgency->publishedAgencyIdentifier, (string)$agency->publishedAgencyIdentifier());
        $this->assertSame($dummyAgency->language->value, $agency->language()->value);
        $this->assertSame((string)$dummyAgency->name, (string)$agency->name());
        $this->assertSame($dummyAgency->normalizedName, $agency->normalizedName());
        $this->assertSame((string)$dummyAgency->CEO, (string)$agency->CEO());
        $this->assertSame($dummyAgency->normalizedCEO, $agency->normalizedCEO());
        $this->assertSame($dummyAgency->foundedIn->value(), $agency->foundedIn()->value());
        $this->assertSame((string)$dummyAgency->description, (string)$agency->description());
        $this->assertSame($dummyAgency->status, $agency->status());
    }

    /**
     * 異常系：指定したIDに紐づくAgencyが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundAgency(): void
    {
        $dummyAgency = $this->createDummyEditAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new EditAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(AgencyNotFoundException::class);
        $editAgency = $this->app->make(EditAgencyInterface::class);
        $editAgency->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyAgency = $this->createDummyEditAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new EditAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(PrincipalNotFoundException::class);
        $editAgency = $this->app->make(EditAgencyInterface::class);
        $editAgency->process($input);
    }

    /**
     * 正常系：COLLABORATORがAgencyを編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $dummyAgency = $this->createDummyEditAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);

        $input = new EditAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);
        $draftAgencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyAgency->agency)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $editAgency = $this->app->make(EditAgencyInterface::class);
        $editAgency->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORがAgencyを編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $dummyAgency = $this->createDummyEditAgency();

        $agencyId = (string) $dummyAgency->agencyIdentifier;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new EditAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);
        $draftAgencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyAgency->agency)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $editAgency = $this->app->make(EditAgencyInterface::class);
        $editAgency->process($input);
    }

    /**
     * 正常系：GROUP_ACTORがAgencyを編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithGroupActor(): void
    {
        $dummyAgency = $this->createDummyEditAgency();

        $groupId = StrTestHelper::generateUuid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, null, [$groupId], []);

        $input = new EditAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);
        $draftAgencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyAgency->agency)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $editAgency = $this->app->make(EditAgencyInterface::class);
        $editAgency->process($input);
    }

    /**
     * 異常系：TALENT_ACTORはAgencyを編集できないこと.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithTalentActor(): void
    {
        $dummyAgency = $this->createDummyEditAgency();

        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, null, [$groupId], [$talentId]);

        $input = new EditAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $this->expectException(UnauthorizedException::class);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $editAgency = $this->app->make(EditAgencyInterface::class);
        $editAgency->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがAgencyを編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyAgency = $this->createDummyEditAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $input = new EditAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);
        $draftAgencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyAgency->agency)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $editAgency = $this->app->make(EditAgencyInterface::class);
        $editAgency->process($input);
    }

    /**
     * 異常系：NONEロールがAgencyを編集しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummyAgency = $this->createDummyEditAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new EditAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $this->expectException(UnauthorizedException::class);
        $editAgency = $this->app->make(EditAgencyInterface::class);
        $editAgency->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return EditAgencyTestData
     */
    private function createDummyEditAgency(): EditAgencyTestData
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
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

        $status = ApprovalStatus::Pending;
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

        return new EditAgencyTestData(
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
            $agency,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class EditAgencyTestData
{
    public function __construct(
        public AgencyIdentifier         $agencyIdentifier,
        public AgencyIdentifier         $publishedAgencyIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public AgencyName               $name,
        public string                   $normalizedName,
        public CEO                      $CEO,
        public string                   $normalizedCEO,
        public FoundedIn                $foundedIn,
        public Description              $description,
        public ApprovalStatus           $status,
        public DraftAgency              $agency,
    ) {
    }
}
