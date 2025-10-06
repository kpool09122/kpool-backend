<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\SubmitAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency\SubmitAgency;
use Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency\SubmitAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency\SubmitAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
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
        // TODO: 各実装クラス作ったら削除する
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
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

        $input = new SubmitAgencyInput(
            $agencyIdentifier,
            $principal,
        );

        $status = ApprovalStatus::Pending;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $status,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($agency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $submitAgency = $this->app->make(SubmitAgencyInterface::class);
        $agency = $submitAgency->process($input);
        $this->assertNotSame($status, $agency->status());
        $this->assertSame(ApprovalStatus::UnderReview, $agency->status());
    }

    /**
     * 異常系：指定したIDに紐づくAgencyが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundAgency(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new SubmitAgencyInput(
            $agencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
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
     */
    public function testInvalidStatus(): void
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

        $input = new SubmitAgencyInput(
            $agencyIdentifier,
            $principal,
        );

        $status = ApprovalStatus::Approved;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $status,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
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
     */
    public function testProcessWithCollaborator(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);

        $input = new SubmitAgencyInput($agencyIdentifier, $principal);

        $status = ApprovalStatus::Pending;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            new CEO('J.Y. Park'),
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('JYP엔터테인먼트 설명'),
            $status,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($agency)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(DraftAgency::class, $result);
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
     */
    public function testProcessWithAgencyActor(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $agencyIdentifier;
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], null);

        $input = new SubmitAgencyInput($agencyIdentifier, $principal);

        $status = ApprovalStatus::Pending;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            new CEO('J.Y. Park'),
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('JYP엔터테인먼트 설명'),
            $status,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($agency)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(DraftAgency::class, $result);
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
     */
    public function testProcessWithGroupActor(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$groupId], null);

        $input = new SubmitAgencyInput($agencyIdentifier, $principal);

        $status = ApprovalStatus::Pending;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            new CEO('J.Y. Park'),
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('JYP엔터테인먼트 설명'),
            $status,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($agency)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(DraftAgency::class, $result);
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
     */
    public function testProcessWithTalentActor(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$groupId], $talentId);

        $input = new SubmitAgencyInput($agencyIdentifier, $principal);

        $status = ApprovalStatus::Pending;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            new CEO('J.Y. Park'),
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('JYP엔터테인먼트 설명'),
            $status,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($agency)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $useCase = $this->app->make(SubmitAgencyInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(DraftAgency::class, $result);
        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }
}
