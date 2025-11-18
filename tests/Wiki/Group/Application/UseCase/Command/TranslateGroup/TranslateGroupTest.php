<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\Service\TranslationServiceInterface;
use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroup;
use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateGroupTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $this->assertInstanceOf(TranslateGroup::class, $translateGroup);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
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
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateGroupInput(
            $groupIdentifier,
            $principal,
        );

        $version = new Version(1);
        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $jaGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaName = new GroupName('TWICE');
        $jaAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $jaDescription = new Description('### TWICE：世界を魅了する9人組ガールズグループ
TWICE（トゥワイス）は、2015年に韓国のサバイバルオーディション番組「SIXTEEN」を通じて結成された、JYPエンターテインメント所属の9人組ガールズグループです。メンバーは韓国出身5名（ナヨン、ジョンヨン、ジヒョ、ダヒョン、チェヨン）、日本出身3名（モモ、サナ、ミナ）、台湾出身1名（ツウィ）という多国籍構成で、多様な魅力が集まっています。
グループ名は「良い音楽で一度、素敵なパフォーマンスで二度感動を与える」という意味が込められています。その名の通り、デビュー曲「Like OOH-AHH」以降、「CHEER UP」「TT」「LIKEY」「What is Love?」「FANCY」など、数多くのヒット曲を次々と発表しました。特に「TT」で見せた泣き顔を表現した「TTポーズ」は、日本でも社会現象になるほど大きな人気を得ました。
デビュー初期の明るく可愛らしいコンセプトから年々成長を重ね、洗練されたクールなパフォーマンスまで多彩な姿を見せています。中毒性のあるメロディーと真似しやすい振り付けが特徴で、幅広い世代から支持されています。韓国や日本だけでなく、世界的なスタジアムツアーを成功させるなど、K-POPを代表するトップクラスのグループとして、今もなお世界中のファンを魅了し続けています。ファンダム名は「ONCE（ワンス）」です。');
        $jaSongIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $jaImagePath = new ImagePath('/resources/public/images/after1.webp');
        $jaGroup = new DraftGroup(
            $jaGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaAgencyIdentifier,
            $jaDescription,
            $jaSongIdentifiers,
            $jaImagePath,
            ApprovalStatus::Pending,
        );

        $enGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enName = new GroupName('TWICE');
        $enAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $enDescription = new Description('TWICE: The 9-Member Girl Group That Captivated the World^r
TWICE is a nine-member girl group under JYP Entertainment, formed in 2015 through the South Korean survival audition show "SIXTEEN." The group has a multinational lineup, consisting of five members from Korea (Nayeon, Jeongyeon, Jihyo, Dahyun, Chaeyoung), three from Japan (Momo, Sana, Mina), and one from Taiwan (Tzuyu), bringing together a diverse array of charms.
The group\'s name holds the meaning, "to touch people\'s hearts once through good music and twice through great performances.\" True to their name, since their debut track \"Like OOH-AHH,\" they have released a string of numerous hit songs, including \"CHEER UP,\" \"TT,\" \"LIKEY,\" \"What is Love?,\" and \"FANCY.\" In particular, the \"TT pose,\" which mimics a crying emoticon and was featured in the song "TT," became so popular in Japan that it turned into a social phenomenon.
    From their bright and cute concept in their early debut years, they have continued to grow each year, showcasing a versatile image that extends to sophisticated and cool performances. Characterized by their addictive melodies and easy-to-follow choreography, they have garnered support from a wide range of generations. Not only in Korea and Japan, but as a top-tier group representing K-pop, they continue to captivate fans worldwide by successfully holding global stadium tours. Their official fandom name is \"ONCE.\"');
        $enSongIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $enImagePath = new ImagePath('/resources/public/images/after2.webp');
        $enGroup = new DraftGroup(
            $enGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enAgencyIdentifier,
            $enDescription,
            $enSongIdentifiers,
            $enImagePath,
            ApprovalStatus::Pending
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($groupIdentifier)
            ->once()
            ->andReturn($group);
        $groupRepository->shouldReceive('saveDraft')
            ->with($enGroup)
            ->once()
            ->andReturn(null);
        $groupRepository->shouldReceive('saveDraft')
            ->with($jaGroup)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $english)
            ->once()
            ->andReturn($enGroup);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $japanese)
            ->once()
            ->andReturn($jaGroup);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);
        $this->assertCount(2, $groups);
        $this->assertSame($jaGroup, $groups[0]);
        $this->assertSame($enGroup, $groups[1]);
    }

    /**
     * 異常系： 指定したIDのグループ情報が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenAgencyNotFound(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateGroupInput(
            $groupIdentifier,
            $principal,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($groupIdentifier)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $this->expectException(GroupNotFoundException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 異常系：翻訳権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], []);

        $input = new TranslateGroupInput(
            $groupIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $description = new Description('### 트와이스');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $version = new Version(1);

        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $this->expectException(UnauthorizedException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 正常系：ADMINISTRATORがグループを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcessWithAdministrator(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/test.webp');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateGroupInput(
            $groupIdentifier,
            $principal,
        );

        $version = new Version(1);
        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $jaGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaName = new GroupName('TWICE');
        $jaAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $jaDescription = new Description('### TWICE');
        $jaSongIdentifiers = [];
        $jaImagePath = new ImagePath('/resources/public/images/ja.webp');
        $jaGroup = new DraftGroup(
            $jaGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaAgencyIdentifier,
            $jaDescription,
            $jaSongIdentifiers,
            $jaImagePath,
            ApprovalStatus::Pending,
        );

        $enGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enName = new GroupName('TWICE');
        $enAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $enDescription = new Description('### TWICE');
        $enSongIdentifiers = [];
        $enImagePath = new ImagePath('/resources/public/images/en.webp');
        $enGroup = new DraftGroup(
            $enGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enAgencyIdentifier,
            $enDescription,
            $enSongIdentifiers,
            $enImagePath,
            ApprovalStatus::Pending,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($groupIdentifier)
            ->once()
            ->andReturn($group);
        $groupRepository->shouldReceive('saveDraft')
            ->with($enGroup)
            ->once()
            ->andReturn(null);
        $groupRepository->shouldReceive('saveDraft')
            ->with($jaGroup)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $english)
            ->once()
            ->andReturn($enGroup);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $japanese)
            ->once()
            ->andReturn($jaGroup);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 異常系：AGENCY_ACTORが他の事務所のグループを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new TranslateGroupInput($groupIdentifier, $principal);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $description = new Description('### 트와이스');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $version = new Version(1);

        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $this->expectException(UnauthorizedException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の事務所に所属するグループを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $agencyId = StrTestHelper::generateUlid();
        $agencyIdentifier = new AgencyIdentifier($agencyId);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new TranslateGroupInput($groupIdentifier, $principal);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $description = new Description('### 트와이스');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $version = new Version(1);

        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $jaGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaName = new GroupName('TWICE');
        $jaAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $jaDescription = new Description('### TWICE');
        $jaSongIdentifiers = [];
        $jaImagePath = new ImagePath('/resources/public/images/ja.webp');
        $jaGroup = new DraftGroup(
            $jaGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaAgencyIdentifier,
            $jaDescription,
            $jaSongIdentifiers,
            $jaImagePath,
            ApprovalStatus::Pending,
        );

        $enGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enName = new GroupName('TWICE');
        $enAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $enDescription = new Description('### TWICE');
        $enSongIdentifiers = [];
        $enImagePath = new ImagePath('/resources/public/images/en.webp');
        $enGroup = new DraftGroup(
            $enGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enAgencyIdentifier,
            $enDescription,
            $enSongIdentifiers,
            $enImagePath,
            ApprovalStatus::Pending,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($groupIdentifier)
            ->once()
            ->andReturn($group);
        $groupRepository->shouldReceive('saveDraft')
            ->with($enGroup)
            ->once()
            ->andReturn(null);
        $groupRepository->shouldReceive('saveDraft')
            ->with($jaGroup)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $english)
            ->once()
            ->andReturn($enGroup);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $japanese)
            ->once()
            ->andReturn($jaGroup);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$anotherGroupId], []);
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$anotherGroupId], []);

        $input = new TranslateGroupInput($groupIdentifier, $principal);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $description = new Description('### 트와이스');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $version = new Version(1);

        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $this->expectException(UnauthorizedException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function testAuthorizedGroupActor(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [(string) $groupIdentifier], []);

        $input = new TranslateGroupInput($groupIdentifier, $principal);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $description = new Description('### 트와이스');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $version = new Version(1);

        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $jaGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaName = new GroupName('TWICE');
        $jaAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $jaDescription = new Description('### TWICE');
        $jaSongIdentifiers = [];
        $jaImagePath = new ImagePath('/resources/public/images/ja.webp');
        $jaGroup = new DraftGroup(
            $jaGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaAgencyIdentifier,
            $jaDescription,
            $jaSongIdentifiers,
            $jaImagePath,
            ApprovalStatus::Pending,
        );

        $enGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enName = new GroupName('TWICE');
        $enAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $enDescription = new Description('### TWICE');
        $enSongIdentifiers = [];
        $enImagePath = new ImagePath('/resources/public/images/en.webp');
        $enGroup = new DraftGroup(
            $enGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enAgencyIdentifier,
            $enDescription,
            $enSongIdentifiers,
            $enImagePath,
            ApprovalStatus::Pending,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($groupIdentifier)
            ->once()
            ->andReturn($group);
        $groupRepository->shouldReceive('saveDraft')
            ->with($enGroup)
            ->once()
            ->andReturn(null);
        $groupRepository->shouldReceive('saveDraft')
            ->with($jaGroup)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $english)
            ->once()
            ->andReturn($enGroup);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $japanese)
            ->once()
            ->andReturn($jaGroup);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 異常系：MEMBER_ACTORが自分の所属していないグループを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     */
    public function testUnauthorizedMemberScope(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$anotherGroupId], [$memberId]);

        $input = new TranslateGroupInput($groupIdentifier, $principal);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $description = new Description('### 트와이스');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $version = new Version(1);

        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $this->expectException(UnauthorizedException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 正常系：MEMBER_ACTORが自分の所属するグループを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function testAuthorizedMemberActor(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [(string) $groupIdentifier], [$memberId]);

        $input = new TranslateGroupInput($groupIdentifier, $principal);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $description = new Description('### 트와이스');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $version = new Version(1);

        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $jaGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaName = new GroupName('TWICE');
        $jaAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $jaDescription = new Description('### TWICE');
        $jaSongIdentifiers = [];
        $jaImagePath = new ImagePath('/resources/public/images/ja.webp');
        $jaGroup = new DraftGroup(
            $jaGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaAgencyIdentifier,
            $jaDescription,
            $jaSongIdentifiers,
            $jaImagePath,
            ApprovalStatus::Pending,
        );

        $enGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enName = new GroupName('TWICE');
        $enAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $enDescription = new Description('### TWICE');
        $enSongIdentifiers = [];
        $enImagePath = new ImagePath('/resources/public/images/en.webp');
        $enGroup = new DraftGroup(
            $enGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enAgencyIdentifier,
            $enDescription,
            $enSongIdentifiers,
            $enImagePath,
            ApprovalStatus::Pending,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($groupIdentifier)
            ->once()
            ->andReturn($group);
        $groupRepository->shouldReceive('saveDraft')
            ->with($enGroup)
            ->once()
            ->andReturn(null);
        $groupRepository->shouldReceive('saveDraft')
            ->with($jaGroup)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $english)
            ->once()
            ->andReturn($enGroup);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $japanese)
            ->once()
            ->andReturn($jaGroup);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがグループを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], []);

        $input = new TranslateGroupInput(
            $groupIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### TWICE');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/after.webp');
        $version = new Version(1);
        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $jaGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaName = new GroupName('TWICE');
        $jaAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $jaDescription = new Description('### TWICE');
        $jaSongIdentifiers = [];
        $jaImagePath = new ImagePath('/resources/public/images/ja.webp');
        $jaGroup = new DraftGroup(
            $jaGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaAgencyIdentifier,
            $jaDescription,
            $jaSongIdentifiers,
            $jaImagePath,
            ApprovalStatus::Pending,
        );

        $enGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enName = new GroupName('TWICE');
        $enAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $enDescription = new Description('### TWICE');
        $enSongIdentifiers = [];
        $enImagePath = new ImagePath('/resources/public/images/en.webp');
        $enGroup = new DraftGroup(
            $enGroupIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enAgencyIdentifier,
            $enDescription,
            $enSongIdentifiers,
            $enImagePath,
            ApprovalStatus::Pending,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($groupIdentifier)
            ->once()
            ->andReturn($group);
        $groupRepository->shouldReceive('saveDraft')
            ->with($enGroup)
            ->once()
            ->andReturn(null);
        $groupRepository->shouldReceive('saveDraft')
            ->with($jaGroup)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $english)
            ->once()
            ->andReturn($enGroup);
        $translationService->shouldReceive('translateGroup')
            ->with($group, $japanese)
            ->once()
            ->andReturn($jaGroup);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 異常系：NONEロールがグループを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], []);

        $input = new TranslateGroupInput(
            $groupIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### TWICE');
        $songIdentifiers = [];
        $imagePath = new ImagePath('/resources/public/images/after.webp');
        $version = new Version(1);

        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($groupIdentifier)
            ->once()
            ->andReturn($group);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $this->expectException(UnauthorizedException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }
}
