<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\RejectGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\UseCase\Command\RejectGroup\RejectGroup;
use Source\Wiki\Group\Application\UseCase\Command\RejectGroup\RejectGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\RejectGroup\RejectGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\GroupHistory;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupHistoryRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupHistoryIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectGroupTest extends TestCase
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
        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $this->assertInstanceOf(RejectGroup::class, $rejectGroup);
    }

    /**
     * 正常系：正しく申請を拒否できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $dummyRejectGroup = $this->createDummyRejectGroup(
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
        );

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyRejectGroup->group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyRejectGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyRejectGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $group = $rejectGroup->process($input);

        $this->assertNotSame($dummyRejectGroup->status, $group->status());
        $this->assertSame(ApprovalStatus::Rejected, $group->status());
    }

    /**
     * 異常系：指定したIDに紐づくGroupが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundGroup(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn(null);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(GroupNotFoundException::class);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $rejectGroup->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(PrincipalNotFoundException::class);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $rejectGroup->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $rejectGroup->process($input);
    }

    /**
     * 異常系：承認権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $rejectGroup->process($input);
    }

    /**
     * 異常系：AGENCY_ACTORが他の事務所のグループを拒否しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherAgencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $anotherAgencyId, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $rejectGroup->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の事務所に所属するグループを拒否できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [], []);

        $dummyRejectGroup = $this->createDummyRejectGroup(
            agencyId: $agencyId,
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
        );

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyRejectGroup->group)
            ->andReturn(null);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyRejectGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyRejectGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $result = $rejectGroup->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：MEMBER_ACTORが自分の所属していないグループを拒否しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedMemberScope(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherGroupId = StrTestHelper::generateUuid();
        $memberId = StrTestHelper::generateUuid();
        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [$anotherGroupId],
            [$memberId],
        );

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $rejectGroup->process($input);
    }

    /**
     * 正常系：MEMBER_ACTORが自分の所属するグループを拒否できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedMemberActor(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $memberId = StrTestHelper::generateUuid();
        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [$groupId],
            [$memberId],
        );

        $dummyRejectGroup = $this->createDummyRejectGroup(
            groupId: $groupId,
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
        );

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyRejectGroup->group)
            ->andReturn(null);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyRejectGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyRejectGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $result = $rejectGroup->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 正常系：SENIOR_COLLABORATORがグループを拒否できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $dummyRejectGroup = $this->createDummyRejectGroup(
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
        );

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyRejectGroup->group)
            ->andReturn(null);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyRejectGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyRejectGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $result = $rejectGroup->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：NONEロールがグループを拒否しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $rejectGroup->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $groupId
     * @param string|null $agencyId
     * @param ApprovalStatus $status
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @return RejectGroupTestData
     */
    private function createDummyRejectGroup(
        ?string $groupId = null,
        ?string $agencyId = null,
        ApprovalStatus $status = ApprovalStatus::UnderReview,
        ?PrincipalIdentifier $operatorIdentifier = null,
    ): RejectGroupTestData {
        $groupIdentifier = new GroupIdentifier($groupId ?? StrTestHelper::generateUuid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUuid());
        $description = new Description(<<<'DESC'
### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 'SIXTEEN'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 '좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 'OOH-AHH하게' 이후, 'CHEER UP', 'TT', 'LIKEY', 'What is Love?', 'FANCY' 등 수많은 히트곡을 연달아 발표했습니다. 특히 'TT'에서 보여준 우는 표정을 표현한 'TT 포즈'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 '원스(ONCE)'입니다.
DESC);
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $group = new DraftGroup(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
            $imagePath,
            $status,
        );

        $historyIdentifier = new GroupHistoryIdentifier(StrTestHelper::generateUuid());
        $history = new GroupHistory(
            $historyIdentifier,
            HistoryActionType::DraftStatusChange,
            $operatorIdentifier ?? new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $group->editorIdentifier(),
            $group->publishedGroupIdentifier(),
            $group->groupIdentifier(),
            ApprovalStatus::UnderReview,
            ApprovalStatus::Rejected,
            null,
            null,
            $group->name(),
            new DateTimeImmutable('now'),
        );

        return new RejectGroupTestData(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $description,
            $imagePath,
            $status,
            $group,
            $historyIdentifier,
            $history,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class RejectGroupTestData
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param GroupIdentifier $publishedGroupIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param PrincipalIdentifier $editorIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param AgencyIdentifier $agencyIdentifier
     * @param Description $description
     * @param ImagePath $imagePath
     * @param ApprovalStatus $status
     * @param DraftGroup $group
     * @param GroupHistoryIdentifier $historyIdentifier
     * @param GroupHistory $history
     */
    public function __construct(
        public GroupIdentifier $groupIdentifier,
        public GroupIdentifier $publishedGroupIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier $editorIdentifier,
        public Language $language,
        public GroupName $name,
        public AgencyIdentifier $agencyIdentifier,
        public Description $description,
        public ImagePath $imagePath,
        public ApprovalStatus $status,
        public DraftGroup $group,
        public GroupHistoryIdentifier $historyIdentifier,
        public GroupHistory $history,
    ) {
    }
}
