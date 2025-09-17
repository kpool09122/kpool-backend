<?php

namespace Tests\SiteManagement\Announcement\UseCase\Query;

use Businesses\SiteManagement\Announcement\UseCase\Query\AnnouncementReadModel;
use DateTimeImmutable;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AnnouncementReadModelTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $announcementId = StrTestHelper::generateUlid();
        $categoryName = 'Updates';
        $title = '🏆 あなたの一票が推しを輝かせる！新機能「グローバル投票」スタート！';
        $content = 'いつもk-poolをご利用いただき、ありがとうございます！
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
これからもk-poolをよろしくお願いいたします。';
        $publishedDate = new DateTimeImmutable();
        $readModel = new AnnouncementReadModel(
            $announcementId,
            $categoryName,
            $title,
            $content,
            $publishedDate,
        );
        $this->assertSame($announcementId, $readModel->announcementId());
        $this->assertSame($categoryName, $readModel->categoryName());
        $this->assertSame($title, $readModel->title());
        $this->assertSame($content, $readModel->content());
        $this->assertSame($publishedDate, $readModel->publishedDate());
        $this->assertSame([
            'announcement_id' => $announcementId,
            'category_name' => $categoryName,
            'title' => $title,
            'content' => $content,
            'published_date' => $publishedDate->format('Y-m-d'),
        ], $readModel->toArray());
    }
}
