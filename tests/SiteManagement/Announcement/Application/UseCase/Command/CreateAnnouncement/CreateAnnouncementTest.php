<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncementOutput;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactoryInterface;
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

class CreateAnnouncementTest extends TestCase
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
        $createAnnouncement = $this->app()->make(CreateAnnouncementInterface::class);
        $this->assertInstanceOf(CreateAnnouncement::class, $createAnnouncement);
    }

    /**
     * 正常系：正しくAnnouncement Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $dummy = $this->createDummyCreateAnnouncementData();

        $input = new CreateAnnouncementInput(
            $dummy->principalIdentifier,
            $dummy->translationSetIdentifier,
            $dummy->language,
            $dummy->category,
            $dummy->title,
            $dummy->content,
            $dummy->publishedDate,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummy->principalIdentifier)
            ->once()
            ->andReturn($dummy->principal);

        $announcementFactory = Mockery::mock(DraftAnnouncementFactoryInterface::class);
        $announcementFactory->shouldReceive('create')
            ->once()
            ->with(
                $dummy->translationSetIdentifier,
                $dummy->language,
                $dummy->category,
                $dummy->title,
                $dummy->content,
                $dummy->publishedDate
            )
            ->andReturn($dummy->draftAnnouncement);

        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);
        $announcementRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummy->draftAnnouncement)
            ->andReturn(null);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->withArgs(static fn (Principal $principal, Action $action, Resource $resource): bool => $action === Action::ANNOUNCEMENT_CREATE && $resource->type() === ResourceType::ANNOUNCEMENT)->andReturn(! str_contains($this->name(), 'NonOperator'));
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app()->instance(DraftAnnouncementFactoryInterface::class, $announcementFactory);
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $createAnnouncement = $this->app()->make(CreateAnnouncementInterface::class);
        $output = new CreateAnnouncementOutput();
        $createAnnouncement->process($input, $output);
        $announcement = $output->draftAnnouncement();
        $this->assertNotNull($announcement);

        $this->assertTrue(UuidValidator::isValid((string) $announcement->announcementIdentifier()));
        $this->assertSame((string) $dummy->translationSetIdentifier, (string) $announcement->translationSetIdentifier());
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
    public function testProcessThrowsUnauthorizedExceptionForNonOperator(): void
    {
        $this->expectException(UnauthorizedException::class);

        $dummy = $this->createDummyCreateAnnouncementData();

        $input = new CreateAnnouncementInput(
            $dummy->principalIdentifier,
            $dummy->translationSetIdentifier,
            $dummy->language,
            $dummy->category,
            $dummy->title,
            $dummy->content,
            $dummy->publishedDate,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($dummy->principalIdentifier)
            ->once()
            ->andReturn($dummy->principal);

        $announcementFactory = Mockery::mock(DraftAnnouncementFactoryInterface::class);
        $announcementRepository = Mockery::mock(AnnouncementRepositoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->withArgs(static fn (Principal $principal, Action $action, Resource $resource): bool => $action === Action::ANNOUNCEMENT_CREATE && $resource->type() === ResourceType::ANNOUNCEMENT)->andReturn(! str_contains($this->name(), 'NonOperator'));
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app()->instance(DraftAnnouncementFactoryInterface::class, $announcementFactory);
        $this->app()->instance(AnnouncementRepositoryInterface::class, $announcementRepository);
        $createAnnouncement = $this->app()->make(CreateAnnouncementInterface::class);
        $createAnnouncement->process($input, new CreateAnnouncementOutput());
    }

    /**
     * @return CreateAnnouncementTestData
     */
    private function createDummyCreateAnnouncementData(): CreateAnnouncementTestData
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
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

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier('00000000-0000-7000-8000-000000000009'),
        );

        $announcementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUuid());
        $draftAnnouncement = new DraftAnnouncement(
            $announcementIdentifier,
            $translationSetIdentifier,
            $language,
            $category,
            $title,
            $content,
            $publishedDate,
        );

        return new CreateAnnouncementTestData(
            $principalIdentifier,
            $translationSetIdentifier,
            $language,
            $category,
            $title,
            $content,
            $publishedDate,
            $principal,
            $announcementIdentifier,
            $draftAnnouncement,
        );
    }
}

readonly class CreateAnnouncementTestData
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Language $language,
        public Category $category,
        public Title $title,
        public Content $content,
        public PublishedDate $publishedDate,
        public Principal $principal,
        public AnnouncementIdentifier $announcementIdentifier,
        public DraftAnnouncement $draftAnnouncement,
    ) {
    }
}
