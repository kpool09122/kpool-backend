<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
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
        $this->assertSame((string)$data->imageLink, (string)$snapshot->imageLink());
        $this->assertSame($data->relevantVideoLinks->toStringArray(), $snapshot->relevantVideoLinks()->toStringArray());
        $this->assertSame($data->version->value(), $snapshot->version()->value());
        $this->assertSame($data->createdAt->format('Y-m-d H:i:s'), $snapshot->createdAt()->format('Y-m-d H:i:s'));
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
        $language = Language::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [];
        $career = new Career('');
        $relevantVideoLinks = new RelevantVideoLinks([]);
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new TalentSnapshot(
            $snapshotIdentifier,
            $talentIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $realName,
            null,
            $groupIdentifiers,
            null,
            $career,
            null,
            $relevantVideoLinks,
            $version,
            $createdAt,
        );

        $this->assertNull($snapshot->agencyIdentifier());
        $this->assertNull($snapshot->birthday());
        $this->assertNull($snapshot->imageLink());
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
        $imageLink = new ImagePath('/resources/public/images/chaeyoung.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=1');
        $link2 = new ExternalContentLink('https://example.youtube.com/watch?v=2');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2]);
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new TalentSnapshot(
            $snapshotIdentifier,
            $talentIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $version,
            $createdAt,
        );

        return new TalentSnapshotTestData(
            snapshotIdentifier: $snapshotIdentifier,
            talentIdentifier: $talentIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            language: $language,
            name: $name,
            realName: $realName,
            agencyIdentifier: $agencyIdentifier,
            groupIdentifiers: $groupIdentifiers,
            birthday: $birthday,
            career: $career,
            imageLink: $imageLink,
            relevantVideoLinks: $relevantVideoLinks,
            version: $version,
            createdAt: $createdAt,
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
        public Language                  $language,
        public TalentName                $name,
        public RealName                  $realName,
        public AgencyIdentifier          $agencyIdentifier,
        public array                     $groupIdentifiers,
        public Birthday                  $birthday,
        public Career                    $career,
        public ImagePath                 $imageLink,
        public RelevantVideoLinks        $relevantVideoLinks,
        public Version                   $version,
        public DateTimeImmutable         $createdAt,
        public TalentSnapshot            $snapshot,
    ) {
    }
}
