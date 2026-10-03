<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdateAnnouncementTest extends TestCase
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
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $updateAnnouncement = $this->app()->make(UpdateAnnouncementInterface::class);
        $this->assertInstanceOf(UpdateAnnouncement::class, $updateAnnouncement);
    }

    /**
     * 正常系：正しくAnnouncement Entityが更新されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AnnouncementNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $dummy = $this->createDummyUpdateAnnouncementData();

        $input = new UpdateAnnouncementInput(
            $dummy->userIdentifier,
            $dummy->announcementIdentifier,
            $dummy->category,
            $dummy->title,
            $dummy->content,
            $dummy->publishedDate,
        );

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')
            ->with($dummy->userIdentifier)
            ->once()
            ->andReturn($dummy->user);

        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $announcementRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummy->draftAnnouncement)
            ->andReturn(null);
        $announcementRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummy->announcementIdentifier)
            ->andReturn($dummy->draftAnnouncement);

        $this->app()->instance(UserRepositoryInterface::class, $userRepository);
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $updateAnnouncement = $this->app()->make(UpdateAnnouncementInterface::class);
        $announcement = $updateAnnouncement->process($input);
        $this->assertSame((string) $dummy->announcementIdentifier, (string) $announcement->announcementIdentifier());
        $this->assertSame($dummy->language->value, $announcement->translation()->value);
        $this->assertSame($dummy->category->value, $announcement->category()->value);
        $this->assertSame((string) $dummy->title, (string) $announcement->title());
        $this->assertSame((string) $dummy->content, (string) $announcement->content());
        $this->assertSame($dummy->publishedDate->value(), $announcement->publishedDate()->value());
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

        $dummy = $this->createDummyUpdateAnnouncementData(Role::NONE);

        $input = new UpdateAnnouncementInput(
            $dummy->userIdentifier,
            $dummy->announcementIdentifier,
            $dummy->category,
            $dummy->title,
            $dummy->content,
            $dummy->publishedDate,
        );

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')
            ->with($dummy->userIdentifier)
            ->once()
            ->andReturn($dummy->user);

        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);

        $this->app()->instance(UserRepositoryInterface::class, $userRepository);
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $updateAnnouncement = $this->app()->make(UpdateAnnouncementInterface::class);
        $updateAnnouncement->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくAnnouncementが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundGroup(): void
    {
        $dummy = $this->createDummyUpdateAnnouncementData();

        $input = new UpdateAnnouncementInput(
            $dummy->userIdentifier,
            $dummy->announcementIdentifier,
            $dummy->category,
            $dummy->title,
            $dummy->content,
            $dummy->publishedDate,
        );

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')
            ->with($dummy->userIdentifier)
            ->once()
            ->andReturn($dummy->user);

        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $announcementRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummy->announcementIdentifier)
            ->andReturn(null);

        $this->app()->instance(UserRepositoryInterface::class, $userRepository);
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $this->expectException(AnnouncementNotFoundException::class);
        $updateAnnouncement = $this->app()->make(UpdateAnnouncementInterface::class);
        $updateAnnouncement->process($input);
    }

    /**
     * @param Role $role
     * @return UpdateAnnouncementTestData
     */
    private function createDummyUpdateAnnouncementData(Role $role = Role::ADMIN): UpdateAnnouncementTestData
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUuid());
        $announcementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::JAPANESE;
        $category = Category::UPDATES;
        $title = new Title('🏆 あなたの一票が推しを輝かせる！新機能「グローバル投票」スタート！');
        $content = new Content('いつもk-poolをご利用いただき、ありがとうございます！
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
        $publishedDate = new PublishedDate(new DateTimeImmutable());

        $user = new User(
            $userIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $role,
        );

        $draftAnnouncement = new DraftAnnouncement(
            $announcementIdentifier,
            $translationSetIdentifier,
            $language,
            $category,
            $title,
            $content,
            $publishedDate,
        );

        return new UpdateAnnouncementTestData(
            $userIdentifier,
            $announcementIdentifier,
            $language,
            $category,
            $title,
            $content,
            $publishedDate,
            $user,
            $draftAnnouncement,
        );
    }
}

readonly class UpdateAnnouncementTestData
{
    public function __construct(
        public UserIdentifier $userIdentifier,
        public AnnouncementIdentifier $announcementIdentifier,
        public Language $language,
        public Category $category,
        public Title $title,
        public Content $content,
        public PublishedDate $publishedDate,
        public User $user,
        public DraftAnnouncement $draftAnnouncement,
    ) {
    }
}
