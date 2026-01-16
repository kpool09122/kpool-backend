<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;
use Source\Wiki\Talent\Domain\Repository\TalentSnapshotRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;
use Tests\Helper\CreateGroup;
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
        $snapshotId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $language = Language::KOREAN;
        $name = '채영';
        $realName = '손채영';
        $agencyId = StrTestHelper::generateUuid();
        $groupId1 = StrTestHelper::generateUuid();
        $groupId2 = StrTestHelper::generateUuid();

        // 先にグループを作成
        CreateGroup::create($groupId1);
        CreateGroup::create($groupId2);

        $groupIdentifiers = [
            new GroupIdentifier($groupId1),
            new GroupIdentifier($groupId2),
        ];
        $birthday = new DateTimeImmutable('1999-04-23');
        $career = 'TWICE member since 2015.';
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
            'version' => $version,
        ]);

        // 中間テーブルの確認
        $this->assertDatabaseHas('talent_snapshot_group', [
            'talent_snapshot_id' => $snapshotId,
            'group_id' => $groupId1,
        ]);
        $this->assertDatabaseHas('talent_snapshot_group', [
            'talent_snapshot_id' => $snapshotId,
            'group_id' => $groupId2,
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
        $snapshotId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();

        $snapshot = new TalentSnapshot(
            new TalentSnapshotIdentifier($snapshotId),
            new TalentIdentifier($talentId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new TalentName('채영'),
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
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
        $talentId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();

        // バージョン1のスナップショット
        $snapshotId1 = StrTestHelper::generateUuid();
        CreateTalentSnapshot::create($snapshotId1, [
            'talent_id' => $talentId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => '채영 v1',
            'career' => 'Career v1',
            'version' => 1,
            'created_at' => '2024-01-01 00:00:00',
        ]);

        // バージョン2のスナップショット
        $snapshotId2 = StrTestHelper::generateUuid();
        CreateTalentSnapshot::create($snapshotId2, [
            'talent_id' => $talentId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => '채영 v2',
            'career' => 'Career v2',
            'version' => 2,
            'created_at' => '2024-01-02 00:00:00',
        ]);

        // 別のTalentのスナップショット（取得されないはず）
        $otherTalentId = StrTestHelper::generateUuid();
        $snapshotId3 = StrTestHelper::generateUuid();
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
            new TalentIdentifier(StrTestHelper::generateUuid())
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
        $talentId = StrTestHelper::generateUuid();
        $snapshotId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $name = '채영';
        $realName = '손채영';
        $agencyId = StrTestHelper::generateUuid();
        $birthday = '1999-04-23';
        $career = 'TWICE member since 2015.';
        $version = 3;

        CreateTalentSnapshot::create($snapshotId, [
            'talent_id' => $talentId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => $name,
            'real_name' => $realName,
            'agency_id' => $agencyId,
            'birthday' => $birthday,
            'career' => $career,
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
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new Version(1)
        );

        $this->assertNull($snapshot);
    }

    /**
     * 正常系：TranslationSetIdentifierとVersionで複数のスナップショットが取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierAndVersion(): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $version = 2;

        // 同じtranslationSetIdentifierとversionを持つ2つのスナップショットを作成
        $snapshotId1 = StrTestHelper::generateUuid();
        $talentId1 = StrTestHelper::generateUuid();
        CreateTalentSnapshot::create($snapshotId1, [
            'talent_id' => $talentId1,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => '채영',
            'career' => 'Career v2 KR',
            'version' => $version,
        ]);

        $snapshotId2 = StrTestHelper::generateUuid();
        $talentId2 = StrTestHelper::generateUuid();
        CreateTalentSnapshot::create($snapshotId2, [
            'talent_id' => $talentId2,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::ENGLISH->value,
            'name' => 'Chaeyoung',
            'career' => 'Career v2 EN',
            'version' => $version,
        ]);

        // 同じtranslationSetIdentifierだが異なるversion
        $snapshotId3 = StrTestHelper::generateUuid();
        CreateTalentSnapshot::create($snapshotId3, [
            'talent_id' => $talentId1,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => '채영 v1',
            'version' => 1,
        ]);

        // 異なるtranslationSetIdentifier
        $snapshotId4 = StrTestHelper::generateUuid();
        CreateTalentSnapshot::create($snapshotId4, [
            'talent_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'name' => '지효',
            'version' => $version,
        ]);

        $repository = $this->app->make(TalentSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTranslationSetIdentifierAndVersion(
            new TranslationSetIdentifier($translationSetIdentifier),
            new Version($version)
        );

        $this->assertCount(2, $snapshots);
        $snapshotIds = array_map(
            static fn (TalentSnapshot $snapshot): string => (string) $snapshot->snapshotIdentifier(),
            $snapshots
        );
        $this->assertContains($snapshotId1, $snapshotIds);
        $this->assertContains($snapshotId2, $snapshotIds);
    }

    /**
     * 正常系：該当するスナップショットが存在しない場合、空配列が返却されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierAndVersionWhenNoSnapshots(): void
    {
        $repository = $this->app->make(TalentSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTranslationSetIdentifierAndVersion(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Version(1)
        );

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }
}
