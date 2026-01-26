<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentSnapshotTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $data = $this->createDummyTalentSnapshot();
        $snapshot = $data->snapshot;

        $this->assertSame((string)$data->snapshotIdentifier, (string)$snapshot->snapshotIdentifier());
        $this->assertSame((string)$data->talentIdentifier, (string)$snapshot->talentIdentifier());
        $this->assertSame((string)$data->translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame($data->language->value, $snapshot->language()->value);
        $this->assertSame((string)$data->name, (string)$snapshot->name());
        $this->assertSame((string)$data->realName, (string)$snapshot->realName());
        $this->assertSame((string)$data->agencyIdentifier, (string)$snapshot->agencyIdentifier());
        $this->assertSame($data->groupIdentifiers, $snapshot->groupIdentifiers());
        $this->assertSame($data->birthday->value(), $snapshot->birthday()->value());
        $this->assertSame((string)$data->career, (string)$snapshot->career());
        $this->assertSame($data->version->value(), $snapshot->version()->value());
        $this->assertSame($data->createdAt->format('Y-m-d H:i:s'), $snapshot->createdAt()->format('Y-m-d H:i:s'));
        $this->assertSame((string)$data->editorIdentifier, (string)$snapshot->editorIdentifier());
        $this->assertSame((string)$data->approverIdentifier, (string)$snapshot->approverIdentifier());
        $this->assertSame((string)$data->mergerIdentifier, (string)$snapshot->mergerIdentifier());
        $this->assertSame($data->mergedAt->format('Y-m-d H:i:s'), $snapshot->mergedAt()->format('Y-m-d H:i:s'));
        $this->assertSame((string)$data->sourceEditorIdentifier, (string)$snapshot->sourceEditorIdentifier());
        $this->assertSame($data->translatedAt->format('Y-m-d H:i:s'), $snapshot->translatedAt()->format('Y-m-d H:i:s'));
        $this->assertSame($data->approvedAt->format('Y-m-d H:i:s'), $snapshot->approvedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系: agencyIdentifierがnullでもインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullAgencyIdentifier(): void
    {
        $snapshotIdentifier = new TalentSnapshotIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('chaeyoung');
        $language = Language::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [];
        $career = new Career('');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new TalentSnapshot(
            $snapshotIdentifier,
            $talentIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $realName,
            null,
            $groupIdentifiers,
            null,
            $career,
            $version,
            $createdAt,
        );

        $this->assertNull($snapshot->agencyIdentifier());
        $this->assertNull($snapshot->birthday());
        $this->assertNull($snapshot->editorIdentifier());
        $this->assertNull($snapshot->approverIdentifier());
        $this->assertNull($snapshot->mergerIdentifier());
        $this->assertNull($snapshot->mergedAt());
        $this->assertNull($snapshot->sourceEditorIdentifier());
        $this->assertNull($snapshot->translatedAt());
        $this->assertNull($snapshot->approvedAt());
    }

    /**
     * ダミーのTalentSnapshotを作成するヘルパーメソッド
     *
     * @return TalentSnapshotTestData
     */
    private function createDummyTalentSnapshot(): TalentSnapshotTestData
    {
        $snapshotIdentifier = new TalentSnapshotIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('chaeyoung');
        $language = Language::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1999-04-23'));
        $career = new Career('TWICE member since 2015.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2024-01-02 00:00:00');
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translatedAt = new DateTimeImmutable('2024-01-03 00:00:00');
        $approvedAt = new DateTimeImmutable('2024-01-04 00:00:00');

        $snapshot = new TalentSnapshot(
            $snapshotIdentifier,
            $talentIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $version,
            $createdAt,
            $editorIdentifier,
            $approverIdentifier,
            $mergerIdentifier,
            $mergedAt,
            $sourceEditorIdentifier,
            $translatedAt,
            $approvedAt,
        );

        return new TalentSnapshotTestData(
            snapshotIdentifier: $snapshotIdentifier,
            talentIdentifier: $talentIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            slug: $slug,
            language: $language,
            name: $name,
            realName: $realName,
            agencyIdentifier: $agencyIdentifier,
            groupIdentifiers: $groupIdentifiers,
            birthday: $birthday,
            career: $career,
            version: $version,
            createdAt: $createdAt,
            editorIdentifier: $editorIdentifier,
            approverIdentifier: $approverIdentifier,
            mergerIdentifier: $mergerIdentifier,
            mergedAt: $mergedAt,
            sourceEditorIdentifier: $sourceEditorIdentifier,
            translatedAt: $translatedAt,
            approvedAt: $approvedAt,
            snapshot: $snapshot,
        );
    }
}

/**
 * テストデータを保持するクラス
 * @phpstan-type GroupIdentifierList list<GroupIdentifier>
 */
readonly class TalentSnapshotTestData
{
    /**
     * @param GroupIdentifier[] $groupIdentifiers
     */
    public function __construct(
        public TalentSnapshotIdentifier  $snapshotIdentifier,
        public TalentIdentifier          $talentIdentifier,
        public TranslationSetIdentifier  $translationSetIdentifier,
        public Slug                      $slug,
        public Language                  $language,
        public TalentName                $name,
        public RealName                  $realName,
        public AgencyIdentifier          $agencyIdentifier,
        public array                     $groupIdentifiers,
        public Birthday                  $birthday,
        public Career                    $career,
        public Version                   $version,
        public DateTimeImmutable         $createdAt,
        public PrincipalIdentifier       $editorIdentifier,
        public PrincipalIdentifier       $approverIdentifier,
        public PrincipalIdentifier       $mergerIdentifier,
        public DateTimeImmutable         $mergedAt,
        public PrincipalIdentifier       $sourceEditorIdentifier,
        public DateTimeImmutable         $translatedAt,
        public DateTimeImmutable         $approvedAt,
        public TalentSnapshot            $snapshot,
    ) {
    }
}
