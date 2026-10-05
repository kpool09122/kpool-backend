<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementOutput;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishAnnouncementTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $announcementFactory = Mockery::mock(AnnouncementFactoryInterface::class);
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $this->app()->instance(AnnouncementFactoryInterface::class, $announcementFactory);
        $publishAnnouncement = $this->app()->make(PublishAnnouncementInterface::class);
        $this->assertInstanceOf(PublishAnnouncement::class, $publishAnnouncement);
    }

    /**
     * 正常系：正しくAnnouncement Entityが公開されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $dummy = $this->createDummyPublishAnnouncementData();

        $input = new PublishAnnouncementInput(
            $dummy->principalIdentifier,
            $dummy->translationSetIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummy->principalIdentifier)
            ->once()
            ->andReturn($dummy->principal);

        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $announcementRepository->shouldReceive('findDraftsByTranslationSetIdentifier')
            ->once()
            ->with($dummy->translationSetIdentifier)
            ->andReturn([$dummy->jaDraftAnnouncement, $dummy->koDraftAnnouncement, $dummy->enDraftAnnouncement]);
        $announcementRepository->shouldReceive('save')
            ->once()
            ->with($dummy->koAnnouncement)
            ->andReturn(null);
        $announcementRepository->shouldReceive('save')
            ->once()
            ->with($dummy->jaAnnouncement)
            ->andReturn(null);
        $announcementRepository->shouldReceive('save')
            ->once()
            ->with($dummy->enAnnouncement)
            ->andReturn(null);
        $announcementRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummy->koDraftAnnouncement)
            ->andReturn(null);
        $announcementRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummy->jaDraftAnnouncement)
            ->andReturn(null);
        $announcementRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummy->enDraftAnnouncement)
            ->andReturn(null);

        $announcementFactory = Mockery::mock(AnnouncementFactoryInterface::class);
        $announcementFactory->shouldReceive('create')
            ->once()
            ->with(
                $dummy->translationSetIdentifier,
                Language::KOREAN,
                $dummy->category,
                $dummy->koTitle,
                $dummy->koContent,
                $dummy->publishedDate,
            )
            ->andReturn($dummy->koAnnouncement);
        $announcementFactory->shouldReceive('create')
            ->once()
            ->with(
                $dummy->translationSetIdentifier,
                Language::JAPANESE,
                $dummy->category,
                $dummy->jaTitle,
                $dummy->jaContent,
                $dummy->publishedDate,
            )
            ->andReturn($dummy->jaAnnouncement);
        $announcementFactory->shouldReceive('create')
            ->once()
            ->with(
                $dummy->translationSetIdentifier,
                Language::ENGLISH,
                $dummy->category,
                $dummy->enTitle,
                $dummy->enContent,
                $dummy->publishedDate,
            )
            ->andReturn($dummy->enAnnouncement);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->withArgs(static fn (Principal $principal, Action $action, Resource $resource): bool => $action === Action::ANNOUNCEMENT_PUBLISH && $resource->type() === ResourceType::ANNOUNCEMENT)->andReturn(! str_contains($this->name(), 'NonAdmin'));
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $this->app()->instance(AnnouncementFactoryInterface::class, $announcementFactory);
        $publishAnnouncement = $this->app()->make(PublishAnnouncementInterface::class);
        $output = new PublishAnnouncementOutput();
        $publishAnnouncement->process($input, $output);
        $announcements = $output->announcements();
        $this->assertSame((string) $dummy->jaAnnouncementIdentifier, (string) $announcements[0]->announcementIdentifier());
        $this->assertSame((string) $dummy->koAnnouncementIdentifier, (string) $announcements[1]->announcementIdentifier());
        $this->assertSame((string) $dummy->enAnnouncementIdentifier, (string) $announcements[2]->announcementIdentifier());
    }

    /**
     * 異常系：ADMIN以外のユーザーはUnauthorizedExceptionがスローされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessThrowsUnauthorizedExceptionForNonAdmin(): void
    {
        $this->expectException(UnauthorizedException::class);

        $dummy = $this->createDummyPublishAnnouncementData();

        $input = new PublishAnnouncementInput(
            $dummy->principalIdentifier,
            $dummy->translationSetIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummy->principalIdentifier)
            ->once()
            ->andReturn($dummy->principal);

        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $announcementFactory = Mockery::mock(AnnouncementFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->withArgs(static fn (Principal $principal, Action $action, Resource $resource): bool => $action === Action::ANNOUNCEMENT_PUBLISH && $resource->type() === ResourceType::ANNOUNCEMENT)->andReturn(! str_contains($this->name(), 'NonAdmin'));
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $this->app()->instance(AnnouncementFactoryInterface::class, $announcementFactory);
        $publishAnnouncement = $this->app()->make(PublishAnnouncementInterface::class);
        $publishAnnouncement->process($input, new PublishAnnouncementOutput());
    }

    /**
     * 正常系：指定したTranslationSetIDに紐づくAnnouncementが存在しない場合、空配列が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundAnnouncement(): void
    {
        $dummy = $this->createDummyPublishAnnouncementData();

        $input = new PublishAnnouncementInput(
            $dummy->principalIdentifier,
            $dummy->translationSetIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummy->principalIdentifier)
            ->once()
            ->andReturn($dummy->principal);

        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $announcementRepository->shouldReceive('findDraftsByTranslationSetIdentifier')
            ->once()
            ->with($dummy->translationSetIdentifier)
            ->andReturn([]);

        $announcementFactory = Mockery::mock(AnnouncementFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->withArgs(static fn (Principal $principal, Action $action, Resource $resource): bool => $action === Action::ANNOUNCEMENT_PUBLISH && $resource->type() === ResourceType::ANNOUNCEMENT)->andReturn(! str_contains($this->name(), 'NonAdmin'));
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $this->app()->instance(AnnouncementFactoryInterface::class, $announcementFactory);
        $publishAnnouncement = $this->app()->make(PublishAnnouncementInterface::class);
        $output = new PublishAnnouncementOutput();
        $publishAnnouncement->process($input, $output);
        $publishAnnouncements = $output->announcements();
        $this->assertEmpty($publishAnnouncements);
    }

    /**
     * @return PublishAnnouncementTestData
     */
    private function createDummyPublishAnnouncementData(): PublishAnnouncementTestData
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $category = Category::UPDATES;
        $publishedDate = new PublishedDate(new DateTimeImmutable());

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
        );

        $jaTitle = new Title('🏆 あなたの一票が推しを輝かせる！新機能「グローバル投票」スタート！');
        $jaContent = new Content('いつもk-poolをご利用いただき、ありがとうございます！
K-popを愛するすべてのファンの皆さまに、もっと「推し活」を楽しんでいただくための新機能、**「グローバル投票」**が本日よりスタートしました！🎉
## 「グローバル投票」でできること
「グローバル投票」は、あなたの"推し"を世界中のファンと一緒に応援できる、新しいリアルタイム投票イベントです。
### 開催される投票イベントの例
* **🏆 今週のベストパフォーマンス:** 各音楽番組のステージから、最高のパフォーマンスをみんなで決定！
* **🎂 センイル（誕生日）広告投票:** 投票で1位になったアイドルの誕生日広告を、街の大型ビジョンに掲載します！
* **✨ 次のカムバコンセプト投票:** ファンの声で次のカムバックコンセプトが決まるかも！？
* **🎤 最高のボーカリストは誰？:** グループの垣根を越えて、No.1ボーカリストをファンの投票で選びます。
あなたの「一票」が、推しのアーティストの新たな伝説を作る力になります！
## 投票への参加方法
参加はとっても簡単！
1.  ホーム画面に追加された**「VOTE」**タブをタップします。
2.  現在開催中の投票イベント一覧から、参加したいイベントを選びます。
3.  応援したいアーティストや楽曲に投票してください！
投票には、毎日のログインやミッションクリアで獲得できる「投票チケット」が必要です。今すぐログインして、最初のチケットをゲットしよう！
詳しい参加方法は、以下のガイドをご確認ください。
[ヘルプ：グローバル投票への参加ガイド](https://example.com/help/global-voting-guide)
## さあ、世界中のファンと繋がろう！
この「グローバル投票」機能が、ファンの皆さまの熱い想いを一つにし、アーティストをさらに大きなステージへと押し上げるきっかけになることを願っています。
今すぐ投票に参加して、あなたの愛を"推し"に届けましょう！
これからもk-poolをよろしくお願いいたします。');
        $jaDraftIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUuid());
        $jaDraftAnnouncement = new DraftAnnouncement(
            $jaDraftIdentifier,
            $translationSetIdentifier,
            Language::JAPANESE,
            $category,
            $jaTitle,
            $jaContent,
            $publishedDate,
        );
        $jaAnnouncementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUuid());
        $jaAnnouncement = new Announcement(
            $jaAnnouncementIdentifier,
            $translationSetIdentifier,
            Language::JAPANESE,
            $category,
            $jaTitle,
            $jaContent,
            $publishedDate,
        );

        $koTitle = new Title('🏆 당신의 한 표가 최애를 빛나게 합니다! 새로운 기능 「글로벌 투표」 시작!');
        $koContent = new Content('항상 k-pool을 이용해 주셔서 감사합니다!
K-POP을 사랑하는 모든 팬 여러분이 "최애 활동"을 더욱 즐겁게 하실 수 있도록 새로운 기능인 **「글로벌 투표」**가 오늘부터 시작되었습니다! 🎉
## 「글로벌 투표」로 할 수 있는 것
「글로벌 투표」는 당신의 "최애"를 전 세계 팬들과 함께 응원할 수 있는 새로운 실시간 투표 이벤트입니다.
### 개최되는 투표 이벤트 예시
* **🏆 금주의 베스트 퍼포먼스:** 각 음악 방송 무대 중에서 최고의 퍼포먼스를 모두 함께 결정!
* **🎂 생일 광고 투표:** 투표에서 1위를 한 아이돌의 생일 광고를 거리의 대형 전광판에 게시합니다!
* **✨ 다음 컴백 콘셉트 투표:** 팬들의 목소리로 다음 컴백 콘셉트가 정해질지도!?
* **🎤 최고의 보컬리스트는 누구?:** 그룹의 경계를 넘어 No.1 보컬리스트를 팬들의 투표로 선정합니다.
당신의 「한 표」가 최애 아티스트의 새로운 전설을 만드는 힘이 됩니다!
## 투표 참여 방법
참여 방법은 매우 간단합니다!
1.  홈 화면에 추가된 **「VOTE」** 탭을 터치합니다.
2.  현재 진행 중인 투표 이벤트 목록에서 참여하고 싶은 이벤트를 선택합니다.
3.  응원하고 싶은 아티스트나 곡에 투표해 주세요!
투표에는 매일 로그인이나 미션 클리어를 통해 획득할 수 있는 \'투표 티켓\'이 필요합니다. 지금 바로 로그인해서 첫 티켓을 받아 가세요!
자세한 참여 방법은 아래 가이드를 확인해 주십시오.
[도움말: 글로벌 투표 참여 가이드](https://example.com/help/global-voting-guide)
## 자, 전 세계 팬들과 연결되자!
이 「글로벌 투표」 기능이 팬 여러분의 뜨거운 마음을 하나로 모아, 아티스트를 더욱 큰 무대로 이끌어 올리는 계기가 되기를 바랍니다.
지금 바로 투표에 참여하여 당신의 사랑을 "최애"에게 전하세요!
앞으로도 k-pool을 잘 부탁드립니다.');
        $koDraftIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUuid());
        $koDraftAnnouncement = new DraftAnnouncement(
            $koDraftIdentifier,
            $translationSetIdentifier,
            Language::KOREAN,
            $category,
            $koTitle,
            $koContent,
            $publishedDate,
        );
        $koAnnouncementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUuid());
        $koAnnouncement = new Announcement(
            $koAnnouncementIdentifier,
            $translationSetIdentifier,
            Language::KOREAN,
            $category,
            $koTitle,
            $koContent,
            $publishedDate,
        );

        $enTitle = new Title('🏆 Your Vote Makes Your Favorite Shine! The New "Global Voting" F');
        $enContent = new Content('Thank you for always using k-pool!
To help all K-pop fans enjoy their fan activities even more, our new feature, **"Global Voting,"** launches today! 🎉
## What you can do with "Global Voting"
"Global Voting" is a new, real-time voting event where you can support your favorite artist along with fans from all over the world.
### Examples of Voting Events
* **🏆 Best Performance of the Week:** Let\'s decide the best stage performance from the weekly music shows together!
* **🎂 Birthday Ad Vote:** The idol who wins first place will get a birthday ad displayed on a large digital billboard in the city!
* **✨ Next Comeback Concept Vote:** Your vote could help decide the next comeback concept!?
* **🎤 Who is the Best Vocalist?:** We\'ll cross group boundaries to choose the No. 1 vocalist by fan vote.
Your "one vote" has the power to create a new legend for your favorite artist!
## How to Participate
It\'s super easy to join!
1.  Tap the **"VOTE"** tab added to the home screen.
2.  Choose the event you want to join from the list of current polls.
3.  Vote for the artist or song you want to support!
You\'ll need "Voting Tickets" to participate, which you can get from daily logins and by clearing missions. Log in now to get your first ticket!
For detailed instructions, please check the guide below.
[Help: Guide to Participating in Global Voting](https://example.com/help/global-voting-guide)
## Let\'s Connect with Fans Around the World!
    We hope this "Global Voting" feature will unite the passionate support of fans everywhere and become a force that lifts artists to even bigger stages.
    Join a vote now and deliver your love to your favorite artist!
    Thank you for your continued support of k-pool.');
        $enDraftIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUuid());
        $enDraftAnnouncement = new DraftAnnouncement(
            $enDraftIdentifier,
            $translationSetIdentifier,
            Language::ENGLISH,
            $category,
            $enTitle,
            $enContent,
            $publishedDate,
        );
        $enAnnouncementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUuid());
        $enAnnouncement = new Announcement(
            $enAnnouncementIdentifier,
            $translationSetIdentifier,
            Language::ENGLISH,
            $category,
            $enTitle,
            $enContent,
            $publishedDate,
        );

        return new PublishAnnouncementTestData(
            $principalIdentifier,
            $translationSetIdentifier,
            $category,
            $publishedDate,
            $principal,
            $jaTitle,
            $jaContent,
            $jaDraftAnnouncement,
            $jaAnnouncementIdentifier,
            $jaAnnouncement,
            $koTitle,
            $koContent,
            $koDraftAnnouncement,
            $koAnnouncementIdentifier,
            $koAnnouncement,
            $enTitle,
            $enContent,
            $enDraftAnnouncement,
            $enAnnouncementIdentifier,
            $enAnnouncement,
        );
    }
}

readonly class PublishAnnouncementTestData
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Category $category,
        public PublishedDate $publishedDate,
        public Principal $principal,
        public Title $jaTitle,
        public Content $jaContent,
        public DraftAnnouncement $jaDraftAnnouncement,
        public AnnouncementIdentifier $jaAnnouncementIdentifier,
        public Announcement $jaAnnouncement,
        public Title $koTitle,
        public Content $koContent,
        public DraftAnnouncement $koDraftAnnouncement,
        public AnnouncementIdentifier $koAnnouncementIdentifier,
        public Announcement $koAnnouncement,
        public Title $enTitle,
        public Content $enContent,
        public DraftAnnouncement $enDraftAnnouncement,
        public AnnouncementIdentifier $enAnnouncementIdentifier,
        public Announcement $enAnnouncement,
    ) {
    }
}
