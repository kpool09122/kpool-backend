<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\UseCase\Command\EditAnnouncement;

use Businesses\Shared\ValueObject\Translation;
use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;
use Businesses\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Category;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Content;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Title;
use Businesses\SiteManagement\Announcement\UseCase\Command\UpdateAnnouncement\UpdateAnnouncement;
use Businesses\SiteManagement\Announcement\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInput;
use Businesses\SiteManagement\Announcement\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInterface;
use Businesses\SiteManagement\Announcement\UseCase\Exception\AnnouncementNotFoundException;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class EditAnnouncementTest extends TestCase
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
        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $this->app->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $updateAnnouncement = $this->app->make(UpdateAnnouncementInterface::class);
        $this->assertInstanceOf(UpdateAnnouncement::class, $updateAnnouncement);
    }

    /**
     * 正常系：正しくAnnouncement Entityが更新されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AnnouncementNotFoundException
     */
    public function testProcess(): void
    {
        $announcementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::JAPANESE;
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
        $input = new UpdateAnnouncementInput(
            $announcementIdentifier,
            $category,
            $title,
            $content,
            $publishedDate,
        );

        $announcement = new Announcement(
            $announcementIdentifier,
            $translation,
            $category,
            $title,
            $content,
            $publishedDate,
        );

        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $announcementRepository->shouldReceive('save')
            ->once()
            ->with($announcement)
            ->andReturn(null);
        $announcementRepository->shouldReceive('findById')
            ->once()
            ->with($announcementIdentifier)
            ->andReturn($announcement);

        $this->app->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $updateAnnouncement = $this->app->make(UpdateAnnouncementInterface::class);
        $announcement = $updateAnnouncement->process($input);
        $this->assertSame((string)$announcementIdentifier, (string)$announcement->announcementIdentifier());
        $this->assertSame($translation->value, $announcement->translation()->value);
        $this->assertSame($category->value, $announcement->category()->value);
        $this->assertSame((string)$title, (string)$announcement->title());
        $this->assertSame((string)$content, (string)$announcement->content());
        $this->assertSame($publishedDate->value(), $announcement->publishedDate()->value());
    }

    /**
     * 異常系：指定したIDに紐づくAnnouncementが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenNotFoundGroup(): void
    {
        $announcementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUlid());
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
        $input = new UpdateAnnouncementInput(
            $announcementIdentifier,
            $category,
            $title,
            $content,
            $publishedDate,
        );

        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $announcementRepository->shouldReceive('findById')
            ->once()
            ->with($announcementIdentifier)
            ->andReturn(null);

        $this->app->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $this->expectException(AnnouncementNotFoundException::class);
        $updateAnnouncement = $this->app->make(UpdateAnnouncementInterface::class);
        $updateAnnouncement->process($input);
    }
}
