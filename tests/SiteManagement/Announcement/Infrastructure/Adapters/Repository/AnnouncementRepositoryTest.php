<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement as AnnouncementEntity;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement as DraftAnnouncementEntity;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[PHPUnitGroup('useDb')]
class AnnouncementRepositoryTest extends TestCase
{
    /**
     * 正常系：指定IDのアナウンスを取得できる.
     * @throws BindingResolutionException
     */
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUlid();
        $translationSetId = StrTestHelper::generateUlid();
        $translation = Language::JAPANESE;
        $title = '公開済みのお知らせ';
        $content = '本文です';
        $category = Category::NEWS;
        $publishedDate = new DateTimeImmutable('2024-01-02 10:00:00');

        DB::table('announcements')->insert([
            'id' => $id,
            'translation_set_identifier' => $translationSetId,
            'language' => $translation->value,
            'category' => $category->value,
            'title' => $title,
            'content' => $content,
            'published_date' => $publishedDate->format('Y-m-d H:i:s'),
        ]);

        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $announcement = $repository->findById(new AnnouncementIdentifier($id));

        $this->assertInstanceOf(AnnouncementEntity::class, $announcement);
        $this->assertSame($id, (string)$announcement->announcementIdentifier());
        $this->assertSame($translationSetId, (string)$announcement->translationSetIdentifier());
        $this->assertSame($translation, $announcement->language());
        $this->assertSame($category, $announcement->category());
        $this->assertSame($title, (string)$announcement->title());
        $this->assertSame($content, (string)$announcement->content());
        $this->assertSame($publishedDate->format(DateTimeImmutable::RFC3339_EXTENDED), (string)$announcement->publishedDate());
    }

    /**
     * 正常系：存在しないIDの場合はnullが返る.
     * @throws BindingResolutionException
     */
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $announcement = $repository->findById(new AnnouncementIdentifier(StrTestHelper::generateUlid()));

        $this->assertNull($announcement);
    }

    /**
     * 正常系：翻訳セットIDでアナウンス一覧を取得できる.
     * @throws BindingResolutionException
     */
    public function testFindByTranslationSetIdentifier(): void
    {
        $translationSetId = StrTestHelper::generateUlid();

        $records = [
            [
                'id' => StrTestHelper::generateUlid(),
                'translation_set_identifier' => $translationSetId,
                'language' => Language::JAPANESE->value,
                'category' => Category::NEWS->value,
                'title' => 'JP',
                'content' => 'JP content',
                'published_date' => '2024-01-01 09:00:00',
            ],
            [
                'id' => StrTestHelper::generateUlid(),
                'translation_set_identifier' => $translationSetId,
                'language' => Language::ENGLISH->value,
                'category' => Category::UPDATES->value,
                'title' => 'EN',
                'content' => 'EN content',
                'published_date' => '2024-01-01 10:00:00',
            ],
            [
                'id' => StrTestHelper::generateUlid(),
                'translation_set_identifier' => StrTestHelper::generateUlid(),
                'language' => Language::KOREAN->value,
                'category' => Category::MAINTENANCE->value,
                'title' => 'Other',
                'content' => 'Ignore',
                'published_date' => '2024-01-05 10:00:00',
            ],
        ];

        DB::table('announcements')->insert($records);

        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $announcements = $repository->findByTranslationSetIdentifier(new TranslationSetIdentifier($translationSetId));

        $this->assertCount(2, $announcements);
        $ids = array_map(static fn (AnnouncementEntity $entity): string => (string)$entity->announcementIdentifier(), $announcements);
        $this->assertContains($records[0]['id'], $ids);
        $this->assertContains($records[1]['id'], $ids);
        $this->assertNotContains($records[2]['id'], $ids);
    }

    /**
     * 正常系：下書きアナウンスを取得できる.
     * @throws BindingResolutionException
     */
    public function testFindDraftById(): void
    {
        $id = StrTestHelper::generateUlid();
        $translationSetId = StrTestHelper::generateUlid();
        $translation = Language::KOREAN;
        $title = '드래프트';
        $content = '초안 내용';
        $category = Category::MAINTENANCE;
        $publishedDate = new DateTimeImmutable('2024-02-01 11:30:00');

        DB::table('draft_announcements')->insert([
            'id' => $id,
            'translation_set_identifier' => $translationSetId,
            'language' => $translation->value,
            'category' => $category->value,
            'title' => $title,
            'content' => $content,
            'published_date' => $publishedDate->format('Y-m-d H:i:s'),
        ]);

        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $draft = $repository->findDraftById(new AnnouncementIdentifier($id));

        $this->assertInstanceOf(DraftAnnouncementEntity::class, $draft);
        $this->assertSame($id, (string)$draft->announcementIdentifier());
        $this->assertSame($translationSetId, (string)$draft->translationSetIdentifier());
        $this->assertSame($translation, $draft->translation());
        $this->assertSame($category, $draft->category());
        $this->assertSame($title, (string)$draft->title());
        $this->assertSame($content, (string)$draft->content());
        $this->assertSame($publishedDate->format(DateTimeImmutable::RFC3339_EXTENDED), (string)$draft->publishedDate());
    }

    /**
     * 正常系：存在しないIDの下書きにはnullが返る.
     * @throws BindingResolutionException
     */
    public function testFindDraftByIdWhenNotExist(): void
    {
        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $draft = $repository->findDraftById(new AnnouncementIdentifier(StrTestHelper::generateUlid()));

        $this->assertNull($draft);
    }

    /**
     * 正常系：翻訳セットIDで下書き一覧を取得できる.
     * @throws BindingResolutionException
     */
    public function testFindDraftsByTranslationSetIdentifier(): void
    {
        $translationSetId = new TranslationSetIdentifier(StrTestHelper::generateUlid());

        $draft1 = [
            'id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => (string)$translationSetId,
            'language' => Language::JAPANESE->value,
            'category' => Category::NEWS->value,
            'title' => 'JP Draft',
            'content' => 'JP draft',
            'published_date' => '2024-03-01 09:00:00',
        ];

        $draft2 = [
            'id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => (string)$translationSetId,
            'language' => Language::ENGLISH->value,
            'category' => Category::UPDATES->value,
            'title' => 'EN Draft',
            'content' => 'EN draft',
            'published_date' => '2024-03-01 10:00:00',
        ];

        $other = [
            'id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => Language::KOREAN->value,
            'category' => Category::MAINTENANCE->value,
            'title' => 'Other',
            'content' => 'Other',
            'published_date' => '2024-03-05 12:00:00',
        ];

        DB::table('draft_announcements')->insert([$draft1, $draft2, $other]);

        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSetIdentifier($translationSetId);

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftAnnouncementEntity $entity): string => (string)$entity->announcementIdentifier(), $drafts);
        $this->assertContains($draft1['id'], $draftIds);
        $this->assertContains($draft2['id'], $draftIds);
        $this->assertNotContains($other['id'], $draftIds);
    }

    /**
     * 正常系：翻訳セットに紐づく下書きが無ければ空配列を返す.
     * @throws BindingResolutionException
     */
    public function testFindDraftsByTranslationSetIdentifierWhenNotExist(): void
    {
        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSetIdentifier(
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }

    /**
     * 正常系：アナウンスを保存できる.
     * @throws BindingResolutionException
     */
    public function testSave(): void
    {
        $announcement = new AnnouncementEntity(
            new AnnouncementIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::ENGLISH,
            Category::UPDATES,
            new Title('Saved Title'),
            new Content('Saved content'),
            new PublishedDate(new DateTimeImmutable('2024-04-01 08:00:00')),
        );

        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $repository->save($announcement);

        $this->assertDatabaseHas('announcements', [
            'id' => (string)$announcement->announcementIdentifier(),
            'translation_set_identifier' => (string)$announcement->translationSetIdentifier(),
            'language' => $announcement->language()->value,
            'category' => $announcement->category()->value,
            'title' => (string)$announcement->title(),
            'content' => (string)$announcement->content(),
            'published_date' => $announcement->publishedDate()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 正常系：下書きを保存できる.
     * @throws BindingResolutionException
     */
    public function testSaveDraft(): void
    {
        $draft = new DraftAnnouncementEntity(
            new AnnouncementIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::JAPANESE,
            Category::NEWS,
            new Title('Draft Title'),
            new Content('Draft content'),
            new PublishedDate(new DateTimeImmutable('2024-05-01 09:30:00')),
        );

        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $repository->saveDraft($draft);

        $this->assertDatabaseHas('draft_announcements', [
            'id' => (string)$draft->announcementIdentifier(),
            'translation_set_identifier' => (string)$draft->translationSetIdentifier(),
            'language' => $draft->translation()->value,
            'category' => $draft->category()->value,
            'title' => (string)$draft->title(),
            'content' => (string)$draft->content(),
            'published_date' => $draft->publishedDate()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 正常系：アナウンスを削除できる.
     * @throws BindingResolutionException
     */
    public function testDelete(): void
    {
        $id = StrTestHelper::generateUlid();

        DB::table('announcements')->insert([
            'id' => $id,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => Language::ENGLISH->value,
            'category' => Category::NEWS->value,
            'title' => 'Delete',
            'content' => 'Delete me',
            'published_date' => '2024-06-01 12:00:00',
        ]);

        $announcement = new AnnouncementEntity(
            new AnnouncementIdentifier($id),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::ENGLISH,
            Category::NEWS,
            new Title('irrelevant'),
            new Content('irrelevant'),
            new PublishedDate(new DateTimeImmutable('2024-06-01 12:00:00')),
        );

        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $repository->delete($announcement);

        $this->assertDatabaseMissing('announcements', ['id' => $id]);
    }

    /**
     * 正常系：下書きを削除できる.
     * @throws BindingResolutionException
     */
    public function testDeleteDraft(): void
    {
        $id = StrTestHelper::generateUlid();

        DB::table('draft_announcements')->insert([
            'id' => $id,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => Language::ENGLISH->value,
            'category' => Category::NEWS->value,
            'title' => 'Delete draft',
            'content' => 'Delete me',
            'published_date' => '2024-07-01 12:00:00',
        ]);

        $draft = new DraftAnnouncementEntity(
            new AnnouncementIdentifier($id),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::ENGLISH,
            Category::NEWS,
            new Title('irrelevant'),
            new Content('irrelevant'),
            new PublishedDate(new DateTimeImmutable('2024-07-01 12:00:00')),
        );

        $repository = $this->app->make(AnnouncementRepositoryInterface::class);
        $repository->deleteDraft($draft);

        $this->assertDatabaseMissing('draft_announcements', ['id' => $id]);
    }
}
