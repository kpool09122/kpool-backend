<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\RejectGroup;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\UseCase\Command\RejectGroup\RejectGroup;
use Source\Wiki\Group\Application\UseCase\Command\RejectGroup\RejectGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\RejectGroup\RejectGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
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
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
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
     */
    public function testProcess(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectGroup->group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
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
     */
    public function testWhenNotFoundGroup(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new RejectGroupInput(
            $groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn(null);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $this->expectException(GroupNotFoundException::class);
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
     */
    public function testInvalidStatus(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        // ステータスがApprovedの場合は例外が発生する
        $status = ApprovalStatus::Approved;
        $group = new DraftGroup(
            $dummyRejectGroup->groupIdentifier,
            $dummyRejectGroup->publishedGroupIdentifier,
            $dummyRejectGroup->translationSetIdentifier,
            $dummyRejectGroup->editorIdentifier,
            $dummyRejectGroup->translation,
            $dummyRejectGroup->name,
            'twice',
            $dummyRejectGroup->agencyIdentifier,
            $dummyRejectGroup->description,
            $dummyRejectGroup->songIdentifiers,
            $dummyRejectGroup->imagePath,
            $status,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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
     */
    public function testUnauthorizedRole(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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
     */
    public function testAuthorizedAgencyActor(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();
        $agencyId = (string) $dummyRejectGroup->agencyIdentifier;

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectGroup->group)
            ->andReturn(null);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $result = $rejectGroup->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループを拒否しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$anotherGroupId], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $rejectGroup->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループを拒否できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedGroupActor(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal(
            $principalIdentifier,
            Role::GROUP_ACTOR,
            null,
            [(string) $dummyRejectGroup->groupIdentifier],
            [],
        );

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectGroup->group)
            ->andReturn(null);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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
     */
    public function testUnauthorizedMemberScope(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal(
            $principalIdentifier,
            Role::TALENT_ACTOR,
            null,
            [$anotherGroupId],
            [$memberId],
        );

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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
     */
    public function testAuthorizedMemberActor(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal(
            $principalIdentifier,
            Role::TALENT_ACTOR,
            null,
            [(string) $dummyRejectGroup->groupIdentifier],
            [$memberId],
        );

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectGroup->group)
            ->andReturn(null);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectGroup->group)
            ->andReturn(null);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

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
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyRejectGroup = $this->createDummyRejectGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], []);

        $input = new RejectGroupInput(
            $dummyRejectGroup->groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectGroup->groupIdentifier)
            ->andReturn($dummyRejectGroup->group);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectGroup = $this->app->make(RejectGroupInterface::class);
        $rejectGroup->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return RejectGroupTestData
     */
    private function createDummyRejectGroup(): RejectGroupTestData
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Language::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            'twice',
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $status,
        );

        return new RejectGroupTestData(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $status,
            $group,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class RejectGroupTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     *
     * @param SongIdentifier[] $songIdentifiers
     */
    public function __construct(
        public GroupIdentifier          $groupIdentifier,
        public GroupIdentifier          $publishedGroupIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public EditorIdentifier         $editorIdentifier,
        public Language                 $translation,
        public GroupName                $name,
        public AgencyIdentifier         $agencyIdentifier,
        public Description              $description,
        public array                    $songIdentifiers,
        public ImagePath                $imagePath,
        public ApprovalStatus           $status,
        public DraftGroup               $group,
    ) {
    }
}
