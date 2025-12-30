<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\SubmitGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\UseCase\Command\SubmitGroup\SubmitGroup;
use Source\Wiki\Group\Application\UseCase\Command\SubmitGroup\SubmitGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\SubmitGroup\SubmitGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\GroupHistory;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupHistoryRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupHistoryIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
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

class SubmitGroupTest extends TestCase
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
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $submitGroup = $this->app->make(SubmitGroupInterface::class);
        $this->assertInstanceOf(SubmitGroup::class, $submitGroup);
    }

    /**
     * 正常系：正しく下書きステータスが変更されること.
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
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $dummySubmitGroup = $this->createDummySubmitGroup(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitGroupInput(
            $dummySubmitGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitGroup->group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitGroup->groupIdentifier)
            ->andReturn($dummySubmitGroup->group);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $submitGroup = $this->app->make(SubmitGroupInterface::class);
        $group = $submitGroup->process($input);
        $this->assertNotSame($dummySubmitGroup->status, $group->status());
        $this->assertSame(ApprovalStatus::UnderReview, $group->status());
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
        $dummySubmitGroup = $this->createDummySubmitGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new SubmitGroupInput(
            $dummySubmitGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitGroup->groupIdentifier)
            ->andReturn(null);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->expectException(GroupNotFoundException::class);
        $submitGroup = $this->app->make(SubmitGroupInterface::class);
        $submitGroup->process($input);
    }

    /**
     * 異常系：承認ステータスがPendingかRejected以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummySubmitGroup = $this->createDummySubmitGroup(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new SubmitGroupInput(
            $dummySubmitGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitGroup->groupIdentifier)
            ->andReturn($dummySubmitGroup->group);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->expectException(InvalidStatusException::class);
        $submitGroup = $this->app->make(SubmitGroupInterface::class);
        $submitGroup->process($input);
    }

    /**
     * 正常系：COLLABORATORがグループを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::COLLABORATOR, null, [], []);

        $dummySubmitGroup = $this->createDummySubmitGroup(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitGroupInput($dummySubmitGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitGroup->groupIdentifier)
            ->andReturn($dummySubmitGroup->group);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitGroup->group)
            ->andReturn(null);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $useCase = $this->app->make(SubmitGroupInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：AGENCY_ACTORがグループを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $dummySubmitGroup = $this->createDummySubmitGroup(
            agencyId: $agencyId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitGroupInput($dummySubmitGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitGroup->groupIdentifier)
            ->andReturn($dummySubmitGroup->group);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitGroup->group)
            ->andReturn(null);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $useCase = $this->app->make(SubmitGroupInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：GROUP_ACTORがグループを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithGroupActor(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::GROUP_ACTOR, null, [$groupId], []);

        $dummySubmitGroup = $this->createDummySubmitGroup(
            groupId: $groupId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitGroupInput($dummySubmitGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitGroup->groupIdentifier)
            ->andReturn($dummySubmitGroup->group);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitGroup->group)
            ->andReturn(null);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $useCase = $this->app->make(SubmitGroupInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：MEMBER_ACTORがグループを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithMemberActor(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::TALENT_ACTOR, null, [$groupId], [$memberId]);

        $dummySubmitGroup = $this->createDummySubmitGroup(
            groupId: $groupId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitGroupInput($dummySubmitGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitGroup->groupIdentifier)
            ->andReturn($dummySubmitGroup->group);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitGroup->group)
            ->andReturn(null);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $useCase = $this->app->make(SubmitGroupInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：SENIOR_COLLABORATORがグループを提出できること.
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
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $dummySubmitGroup = $this->createDummySubmitGroup(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitGroupInput($dummySubmitGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitGroup->groupIdentifier)
            ->andReturn($dummySubmitGroup->group);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitGroup->group)
            ->andReturn(null);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitGroup->history);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $useCase = $this->app->make(SubmitGroupInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 異常系：NONEロールがグループを提出しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummySubmitGroup = $this->createDummySubmitGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::NONE, null, [], []);

        $input = new SubmitGroupInput($dummySubmitGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitGroup->groupIdentifier)
            ->andReturn($dummySubmitGroup->group);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $useCase = $this->app->make(SubmitGroupInterface::class);
        $useCase->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $groupId
     * @param string|null $agencyId
     * @param ApprovalStatus $status
     * @param EditorIdentifier|null $operatorIdentifier
     * @return SubmitGroupTestData
     */
    private function createDummySubmitGroup(
        ?string $groupId = null,
        ?string $agencyId = null,
        ApprovalStatus $status = ApprovalStatus::Pending,
        ?EditorIdentifier $operatorIdentifier = null,
    ): SubmitGroupTestData {
        $groupIdentifier = new GroupIdentifier($groupId ?? StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUlid());
        $description = new Description(<<<'DESC'
### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 'SIXTEEN'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 '좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 'OOH-AHH하게' 이후, 'CHEER UP', 'TT', 'LIKEY', 'What is Love?', 'FANCY' 등 수많은 히트곡을 연달아 발표했습니다. 특히 'TT'에서 보여준 우는 표정을 표현한 'TT 포즈'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라、 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 '원스(ONCE)'입니다.
DESC);
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
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
            $songIdentifiers,
            $imagePath,
            $status,
        );

        $historyIdentifier = new GroupHistoryIdentifier(StrTestHelper::generateUlid());
        $history = new GroupHistory(
            $historyIdentifier,
            $operatorIdentifier ?? new EditorIdentifier(StrTestHelper::generateUlid()),
            $group->editorIdentifier(),
            $group->publishedGroupIdentifier(),
            $group->groupIdentifier(),
            $status,
            ApprovalStatus::UnderReview,
            $group->name(),
            new DateTimeImmutable('now'),
        );

        return new SubmitGroupTestData(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
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
readonly class SubmitGroupTestData
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param GroupIdentifier $publishedGroupIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param EditorIdentifier $editorIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param AgencyIdentifier $agencyIdentifier
     * @param Description $description
     * @param SongIdentifier[] $songIdentifiers
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
        public EditorIdentifier $editorIdentifier,
        public Language $language,
        public GroupName $name,
        public AgencyIdentifier $agencyIdentifier,
        public Description $description,
        public array $songIdentifiers,
        public ImagePath $imagePath,
        public ApprovalStatus $status,
        public DraftGroup $group,
        public GroupHistoryIdentifier $historyIdentifier,
        public GroupHistory $history,
    ) {
    }
}
