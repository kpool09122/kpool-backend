<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Translation;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
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
        $announcement = new Announcement(
            $announcementIdentifier,
            $translation,
            $category,
            $title,
            $content,
            $publishedDate,
        );
        $this->assertSame((string)$announcementIdentifier, (string)$announcement->announcementIdentifier());
        $this->assertSame($translation->value, $announcement->translation()->value);
        $this->assertSame($category->value, $announcement->category()->value);
        $this->assertSame((string)$title, (string)$announcement->title());
        $this->assertSame((string)$content, (string)$announcement->content());
        $this->assertSame($publishedDate->value(), $announcement->publishedDate()->value());
    }

    /**
     * 正常系：Categoryのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetCategory(): void
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
        $announcement = new Announcement(
            $announcementIdentifier,
            $translation,
            $category,
            $title,
            $content,
            $publishedDate,
        );
        $this->assertSame($category->value, $announcement->category()->value);

        $newCategory = Category::NEWS;
        $announcement->setCategory($newCategory);
        $this->assertNotSame($category->value, $announcement->category()->value);
        $this->assertSame($newCategory->value, $announcement->category()->value);
    }

    /**
     * 正常系：Titleのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetTitle(): void
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
        $announcement = new Announcement(
            $announcementIdentifier,
            $translation,
            $category,
            $title,
            $content,
            $publishedDate,
        );
        $this->assertSame((string)$title, (string)$announcement->title());

        $newTitle = new Title('🎬 推しの新しい魅力、見逃してない？メンバー関連動画リンク機能を追加！');
        $announcement->setTitle($newTitle);
        $this->assertNotSame((string)$title, (string)$announcement->title());
        $this->assertSame((string)$newTitle, (string)$announcement->title());
    }

    /**
     * 正常系：FoundedInのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetFoundedIn(): void
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
        $announcement = new Announcement(
            $announcementIdentifier,
            $translation,
            $category,
            $title,
            $content,
            $publishedDate,
        );
        $this->assertSame((string)$content, (string)$announcement->content());

        $newContent = new Content('いつもk-poolをご利用いただき、ありがとうございます！
「このメンバーが活躍している動画だけを、まとめて見たい…！」
そんな多くのファンの皆さまの声にお応えして、各アイドルのプロフィールページに**「関連動画リンク」**機能を追加しました！
## どんな動画が見られるの？
公式コンテンツからファン必見の映像まで、各メンバーの魅力が詰まった様々な動画へのリンクを、プロフィールページから直接チェックできるようになりました。
もう、SNSや動画サイトで一つひとつ検索する必要はありません！
**＜表示される動画の例＞**
* 🎥 **公式Music Video:** そのメンバーがフィーチャーされている公式MV
* 🕺 **チッケム (Fancam):** 音楽番組などで特定メンバーだけを追ったフォーカスカメラ映像
* 😂 **バラエティ番組クリップ:** 出演したバラエティ番組での名場面や面白シーン
* ✍️ **コンテンツ出演動画:** オリジナル企画やライブ配信のアーカイブ映像
* VLOGなど、メンバーの素顔が見える映像
※表示される動画は、最新の活動状況に合わせて随時更新されます。
## 新機能の使い方
使い方はとってもシンプルです。
1.  気になるメンバーのプロフィールページを開きます。
2.  プロフィール情報の下に新しく設置された**「関連動画」**セクションをチェック！
3.  見たい動画のサムネイルやタイトルをタップするだけで、すぐに視聴ページへ移動します。
*(画像はイメージです)*
## 推しの魅力を、もっと深く、もっと手軽に。
この機能を通じて、あなたがまだ知らなかった"推し"の新たな一面を発見したり、お気に入りの瞬間を何度も見返したりする、特別な体験をお届けできれば幸いです。
今すぐお気に入りのメンバーのプロフィールページを訪れて、どんな動画が追加されているかチェックしてみてください！
これからもk-poolは、皆さまの「推し活」を全力でサポートするアップデートを続けていきます。今後ともどうぞよろしくお願いいたします。');
        $announcement->setContent($newContent);
        $this->assertNotSame((string)$content, (string)$announcement->content());
        $this->assertSame((string)$newContent, (string)$announcement->content());
    }

    /**
     * 正常系：Descriptionのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetPublishedDate(): void
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
        $announcement = new Announcement(
            $announcementIdentifier,
            $translation,
            $category,
            $title,
            $content,
            $publishedDate,
        );
        $this->assertSame($publishedDate->value(), $announcement->publishedDate()->value());

        $newPublishedDate = new PublishedDate(new DateTimeImmutable());
        $announcement->setPublishedDate($newPublishedDate);
        $this->assertNotSame($publishedDate->value(), $announcement->publishedDate()->value());
        $this->assertSame($newPublishedDate->value(), $announcement->publishedDate()->value());
    }
}
