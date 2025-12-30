<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;
use Source\Wiki\Talent\Domain\Repository\TalentSnapshotRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;
use Tests\Helper\CreateTalentSnapshot;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentSnapshotRepositoryTest extends TestCase
{
    /**
     * 正常系：スナップショットを保存できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $snapshotId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();
        $translationSetIdentifier = StrTestHelper::generateUlid();
        $language = Language::KOREAN;
        $name = '채영';
        $realName = '손채영';
        $agencyId = StrTestHelper::generateUlid();
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new DateTimeImmutable('1999-04-23');
        $career = 'TWICE member since 2015.';
        $imageLink = '/resources/public/images/chaeyoung.webp';
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=1');
        $link2 = new ExternalContentLink('https://example.youtube.com/watch?v=2');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2]);
        $version = 1;
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new TalentSnapshot(
            new TalentSnapshotIdentifier($snapshotId),
            new TalentIdentifier($talentId),
            new TranslationSetIdentifier($translationSetIdentifier),
            $language,
            new TalentName($name),
            new RealName($realName),
            new AgencyIdentifier($agencyId),
            $groupIdentifiers,
            new Birthday($birthday),
            new Career($career),
            new ImagePath($imageLink),
            $relevantVideoLinks,
            new Version($version),
            $createdAt,
        );

        $repository = $this->app->make(TalentSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('talent_snapshots', [
            'id' => $snapshotId,
            'talent_id' => $talentId,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => $language->value,
            'name' => $name,
            'real_name' => $realName,
            'agency_id' => $agencyId,
            'career' => $career,
            'image_link' => $imageLink,
            'version' => $version,
        ]);
    }

    /**
     * 正常系：agencyIdentifierがnullでもスナップショットを保存できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNullAgencyIdentifier(): void
    {
        $snapshotId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();

        $snapshot = new TalentSnapshot(
            new TalentSnapshotIdentifier($snapshotId),
            new TalentIdentifier($talentId),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new TalentName('채영'),
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            new Version(1),
            new DateTimeImmutable('2024-01-01 00:00:00'),
        );

        $repository = $this->app->make(TalentSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('talent_snapshots', [
            'id' => $snapshotId,
            'talent_id' => $talentId,
            'agency_id' => null,
            'birthday' => null,
            'image_link' => null,
        ]);
    }

    /**
     * 正常系：指定したTalentIDのスナップショット一覧が取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTalentIdentifier(): void
    {
        $talentId = StrTestHelper::generateUlid();
        $translationSetIdentifier = StrTestHelper::generateUlid();

        // バージョン1のスナップショット
        $snapshotId1 = StrTestHelper::generateUlid();
        CreateTalentSnapshot::create($snapshotId1, [
            'talent_id' => $talentId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => '채영 v1',
            'career' => 'Career v1',
            'version' => 1,
            'created_at' => '2024-01-01 00:00:00',
        ]);

        // バージョン2のスナップショット
        $snapshotId2 = StrTestHelper::generateUlid();
        CreateTalentSnapshot::create($snapshotId2, [
            'talent_id' => $talentId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => '채영 v2',
            'career' => 'Career v2',
            'version' => 2,
            'created_at' => '2024-01-02 00:00:00',
        ]);

        // 別のTalentのスナップショット（取得されないはず）
        $otherTalentId = StrTestHelper::generateUlid();
        $snapshotId3 = StrTestHelper::generateUlid();
        CreateTalentSnapshot::create($snapshotId3, [
            'talent_id' => $otherTalentId,
            'name' => '지효',
            'career' => 'TWICE leader.',
        ]);

        $repository = $this->app->make(TalentSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTalentIdentifier(new TalentIdentifier($talentId));

        $this->assertCount(2, $snapshots);
        // バージョン降順で取得されること
        $this->assertSame(2, $snapshots[0]->version()->value());
        $this->assertSame(1, $snapshots[1]->version()->value());
    }

    /**
     * 正常系：該当するスナップショットが存在しない場合、空の配列が返却されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTalentIdentifierWhenNoSnapshots(): void
    {
        $repository = $this->app->make(TalentSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTalentIdentifier(
            new TalentIdentifier(StrTestHelper::generateUlid())
        );

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }

    /**
     * 正常系：指定したTalentIDとバージョンのスナップショットが取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTalentAndVersion(): void
    {
        $talentId = StrTestHelper::generateUlid();
        $snapshotId = StrTestHelper::generateUlid();
        $translationSetIdentifier = StrTestHelper::generateUlid();
        $name = '채영';
        $realName = '손채영';
        $agencyId = StrTestHelper::generateUlid();
        $birthday = '1999-04-23';
        $career = 'TWICE member since 2015.';
        $imageLink = '/resources/public/images/chaeyoung.webp';
        $version = 3;

        CreateTalentSnapshot::create($snapshotId, [
            'talent_id' => $talentId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => $name,
            'real_name' => $realName,
            'agency_id' => $agencyId,
            'birthday' => $birthday,
            'career' => $career,
            'image_link' => $imageLink,
            'version' => $version,
        ]);

        $repository = $this->app->make(TalentSnapshotRepositoryInterface::class);
        $snapshot = $repository->findByTalentAndVersion(
            new TalentIdentifier($talentId),
            new Version($version)
        );

        $this->assertNotNull($snapshot);
        $this->assertSame($snapshotId, (string)$snapshot->snapshotIdentifier());
        $this->assertSame($talentId, (string)$snapshot->talentIdentifier());
        $this->assertSame($translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame(Language::KOREAN, $snapshot->language());
        $this->assertSame($name, (string)$snapshot->name());
        $this->assertSame($realName, (string)$snapshot->realName());
        $this->assertSame($agencyId, (string)$snapshot->agencyIdentifier());
        $this->assertSame($career, (string)$snapshot->career());
        $this->assertSame($imageLink, (string)$snapshot->imageLink());
        $this->assertSame($version, $snapshot->version()->value());
    }

    /**
     * 正常系：該当するスナップショットが存在しない場合、nullが返却されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTalentAndVersionWhenNoSnapshot(): void
    {
        $repository = $this->app->make(TalentSnapshotRepositoryInterface::class);
        $snapshot = $repository->findByTalentAndVersion(
            new TalentIdentifier(StrTestHelper::generateUlid()),
            new Version(1)
        );

        $this->assertNull($snapshot);
    }
}
