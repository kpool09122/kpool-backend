<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\ApproveGroup;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\UseCase\Command\ApproveGroup\ApproveGroup;
use Source\Wiki\Group\Application\UseCase\Command\ApproveGroup\ApproveGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\ApproveGroup\ApproveGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Service\GroupServiceInterface;
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

class ApproveGroupTest extends TestCase
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
        $groupService = Mockery::mock(GroupServiceInterface::class);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $this->assertInstanceOf(ApproveGroup::class, $approveGroup);
    }

    /**
     * 正常系：正しく下書きが承認されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($translationSetIdentifier, $groupIdentifier)
            ->andReturn(false);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $group = $approveGroup->process($input);
        $this->assertNotSame($status, $group->status());
        $this->assertSame(ApprovalStatus::Approved, $group->status());
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
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn(null);

        $groupService = Mockery::mock(GroupServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->expectException(GroupNotFoundException::class);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $approveGroup->process($input);
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
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::Approved;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);

        $this->expectException(InvalidStatusException::class);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $approveGroup->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みの事務所がある場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testHasApprovedButNotTranslatedAgency(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($translationSetIdentifier, $groupIdentifier)
            ->andReturn(true);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);

        $this->expectException(ExistsApprovedButNotTranslatedGroupException::class);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $approveGroup->process($input);
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
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);

        $this->expectException(UnauthorizedException::class);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $approveGroup->process($input);
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループを承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid(); // 異なるグループID
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$anotherGroupId], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);

        $this->expectException(UnauthorizedException::class);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $approveGroup->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループを承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedGroupActor(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [(string) $groupIdentifier], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($translationSetIdentifier, $groupIdentifier)
            ->andReturn(false);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $group = $approveGroup->process($input);
        $this->assertNotSame($status, $group->status());
        $this->assertSame(ApprovalStatus::Approved, $group->status());
    }

    /**
     * 異常系：MEMBER_ACTORが自分の所属していないグループを承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedMemberScope(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid(); // 異なるグループID
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$anotherGroupId], $memberId);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);

        $this->expectException(UnauthorizedException::class);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $approveGroup->process($input);
    }

    /**
     * 正常系：MEMBER_ACTORが自分の所属するグループを承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedMemberActor(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [(string) $groupIdentifier], $memberId);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($translationSetIdentifier, $groupIdentifier)
            ->andReturn(false);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $group = $approveGroup->process($input);
        $this->assertNotSame($status, $group->status());
        $this->assertSame(ApprovalStatus::Approved, $group->status());
    }

    /**
     * 異常系：AGENCY_ACTORが他の事務所のグループを承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid()); // グループの所属事務所
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid(); // 異なる事務所ID
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $anotherAgencyId, [], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);

        $this->expectException(UnauthorizedException::class);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $approveGroup->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の事務所に所属するグループを承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyId = StrTestHelper::generateUlid(); // 事務所ID
        $agencyIdentifier = new AgencyIdentifier($agencyId);
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($translationSetIdentifier, $groupIdentifier)
            ->andReturn(false);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $group = $approveGroup->process($input);
        $this->assertNotSame($status, $group->status());
        $this->assertSame(ApprovalStatus::Approved, $group->status());
    }

    /**
     * 正常系：SENIOR_COLLABORATORがグループを承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('saveDraft')
            ->once()
            ->with($group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($translationSetIdentifier, $groupIdentifier)
            ->andReturn(false);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $group = $approveGroup->process($input);
        $this->assertNotSame($status, $group->status());
        $this->assertSame(ApprovalStatus::Approved, $group->status());
    }

    /**
     * 異常系：NONEロールがグループを承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/before.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], null);

        $input = new ApproveGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $group = new DraftGroup(
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
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $groupService = Mockery::mock(GroupServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);

        $this->expectException(UnauthorizedException::class);
        $approveGroup = $this->app->make(ApproveGroupInterface::class);
        $approveGroup->process($input);
    }
}
