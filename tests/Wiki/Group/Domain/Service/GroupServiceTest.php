<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Service\GroupService;
use Source\Wiki\Group\Domain\Service\GroupServiceInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GroupServiceTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $groupService = $this->app->make(GroupServiceInterface::class);
        $this->assertInstanceOf(GroupService::class, $groupService);
    }

    /**
     * 正常系: Approved状態のDraftGroupが存在する場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedGroupWhenApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        // 承認済みのDraftGroup (韓国語版)
        $approvedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $approvedGroup = new DraftGroup(
            $approvedGroupIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::KOREAN,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹'),
            new ImagePath('/resources/public/images/twice.webp'),
            ApprovalStatus::Approved,
        );

        // 除外対象のDraftGroup (日本語版)
        $excludeGroup = new DraftGroup(
            $excludeGroupIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::JAPANESE,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('### TWICE：世界を魅了する9人組ガールズグループ'),
            new ImagePath('/resources/public/images/twice_ja.webp'),
            ApprovalStatus::Pending,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedGroup, $excludeGroup]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $groupService = $this->app->make(GroupServiceInterface::class);

        $result = $groupService->existsApprovedButNotTranslatedGroup(
            $translationSetIdentifier,
            $excludeGroupIdentifier,
        );

        $this->assertTrue($result);
    }

    /**
     * 正常系: Approved状態のDraftGroupが存在しない場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedGroupWhenNoApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        // Pending状態のDraftGroup (韓国語版)
        $pendingGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $pendingGroup = new DraftGroup(
            $pendingGroupIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::KOREAN,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹'),
            new ImagePath('/resources/public/images/twice.webp'),
            ApprovalStatus::Pending,
        );

        // 除外対象のDraftGroup (日本語版)
        $excludeGroup = new DraftGroup(
            $excludeGroupIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::JAPANESE,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('### TWICE：世界を魅了する9人組ガールズグループ'),
            new ImagePath('/resources/public/images/twice_ja.webp'),
            ApprovalStatus::Pending,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$pendingGroup, $excludeGroup]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $groupService = $this->app->make(GroupServiceInterface::class);

        $result = $groupService->existsApprovedButNotTranslatedGroup(
            $translationSetIdentifier,
            $excludeGroupIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * 正常系: 自分自身がApprovedでも除外されるのでfalseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedGroupWhenOnlySelfIsApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        // 自分自身 (Approved状態だが除外される)
        $selfGroup = new DraftGroup(
            $excludeGroupIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::JAPANESE,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('### TWICE：世界を魅了する9人組ガールズグループ'),
            new ImagePath('/resources/public/images/twice_ja.webp'),
            ApprovalStatus::Approved,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$selfGroup]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $groupService = $this->app->make(GroupServiceInterface::class);

        $result = $groupService->existsApprovedButNotTranslatedGroup(
            $translationSetIdentifier,
            $excludeGroupIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * 正常系: DraftGroupが存在しない場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedGroupWhenNoDrafts(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $groupService = $this->app->make(GroupServiceInterface::class);

        $result = $groupService->existsApprovedButNotTranslatedGroup(
            $translationSetIdentifier,
            $excludeGroupIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * 正常系: 複数のApproved状態のDraftGroupが存在する場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedGroupWhenMultipleApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        // 韓国語版 (Approved)
        $koreanGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $koreanGroup = new DraftGroup(
            $koreanGroupIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::KOREAN,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.'),
            new ImagePath('/resources/public/images/twice_ko.webp'),
            ApprovalStatus::Approved,
        );

        // 英語版 (Approved)
        $englishGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $englishGroup = new DraftGroup(
            $englishGroupIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::ENGLISH,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('TWICE: The 9-Member Girl Group That Captivated the World
TWICE is a nine-member girl group under JYP Entertainment, formed in 2015 through the South Korean survival audition show "SIXTEEN." The group has a multinational lineup, consisting of five members from Korea (Nayeon, Jeongyeon, Jihyo, Dahyun, Chaeyoung), three from Japan (Momo, Sana, Mina), and one from Taiwan (Tzuyu), bringing together a diverse array of charms.
The group\'s name holds the meaning, "to touch people\'s hearts once through good music and twice through great performances." True to their name, since their debut track "Like OOH-AHH," they have released a string of numerous hit songs, including "CHEER UP," "TT," "LIKEY," "What is Love?," and "FANCY." In particular, the "TT pose," which mimics a crying emoticon and was featured in the song "TT," became so popular in Japan that it turned into a social phenomenon.
From their bright and cute concept in their early debut years, they have continued to grow each year, showcasing a versatile image that extends to sophisticated and cool performances. Characterized by their addictive melodies and easy-to-follow choreography, they have garnered support from a wide range of generations. Not only in Korea and Japan, but as a top-tier group representing K-pop, they continue to captivate fans worldwide by successfully holding global stadium tours. Their official fandom name is "ONCE."'),
            new ImagePath('/resources/public/images/twice_en.webp'),
            ApprovalStatus::Approved,
        );

        // 日本語版 (Pending, 除外対象)
        $japaneseGroup = new DraftGroup(
            $excludeGroupIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::JAPANESE,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('### TWICE：世界を魅了する9人組ガールズグループ
TWICE（トゥワイス）は、2015年に韓国のサバイバルオーディション番組「SIXTEEN」を通じて結成された、JYPエンターテインメント所属の9人組ガールズグループです。メンバーは韓国出身5名（ナヨン、ジョンヨン、ジヒョ、ダヒョン、チェヨン）、日本出身3名（モモ、サナ、ミナ）、台湾出身1名（ツウィ）という多国籍構成で、多様な魅力が集まっています。
グループ名は「良い音楽で一度、素敵なパフォーマンスで二度感動を与える」という意味が込められています。その名の通り、デビュー曲「Like OOH-AHH」以降、「CHEER UP」「TT」「LIKEY」「What is Love?」「FANCY」など、数多くのヒット曲を次々と発表しました。特に「TT」で見せた泣き顔を表現した「TTポーズ」は、日本でも社会現象になるほど大きな人気を得ました。
デビュー初期の明るく可愛らしいコンセプトから年々成長を重ね、洗練されたクールなパフォーマンスまで多彩な姿を見せています。中毒性のあるメロディーと真似しやすい振り付けが特徴で、幅広い世代から支持されています。韓国や日本だけでなく、世界的なスタジアムツアーを成功させるなど、K-POPを代表するトップクラスのグループとして、今もなお世界中のファンを魅了し続けています。ファンダム名は「ONCE（ワンス）」です。'),
            new ImagePath('/resources/public/images/twice_ja.webp'),
            ApprovalStatus::Pending,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$koreanGroup, $englishGroup, $japaneseGroup]);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $groupService = $this->app->make(GroupServiceInterface::class);

        $result = $groupService->existsApprovedButNotTranslatedGroup(
            $translationSetIdentifier,
            $excludeGroupIdentifier,
        );

        $this->assertTrue($result);
    }
}
