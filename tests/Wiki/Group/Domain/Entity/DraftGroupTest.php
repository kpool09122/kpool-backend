<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\Entity;

use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftGroupTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $createDraftGroup = $this->createDummyDraftGroup();
        $group = $createDraftGroup->draftGroup;

        $this->assertSame((string)$createDraftGroup->groupIdentifier, (string)$group->groupIdentifier());
        $this->assertSame((string)$createDraftGroup->publishedGroupIdentifier, (string)$group->publishedGroupIdentifier());
        $this->assertSame((string)$createDraftGroup->translationSetIdentifier, (string)$group->translationSetIdentifier());
        $this->assertSame((string)$createDraftGroup->editorIdentifier, (string)$group->editorIdentifier());
        $this->assertSame($createDraftGroup->language->value, $group->language()->value);
        $this->assertSame((string)$createDraftGroup->name, (string)$group->name());
        $this->assertSame($createDraftGroup->normalizedName, $group->normalizedName());
        $this->assertSame((string)$createDraftGroup->agencyIdentifier, (string)$group->agencyIdentifier());
        $this->assertSame((string)$createDraftGroup->description, (string)$group->description());
        $this->assertSame($createDraftGroup->songIdentifiers, $group->songIdentifiers());
        $this->assertSame((string)$createDraftGroup->imagePath, (string)$group->imagePath());
        $this->assertSame($createDraftGroup->status, $group->status());
    }

    /**
     * 正常系：公開済みGroupIdentifierのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetPublishedGroupIdentifier(): void
    {
        $createDraftGroup = $this->createDummyDraftGroup();
        $group = $createDraftGroup->draftGroup;

        $this->assertSame((string)$createDraftGroup->publishedGroupIdentifier, (string)$group->publishedGroupIdentifier());

        $newGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $group->setPublishedGroupIdentifier($newGroupIdentifier);
        $this->assertNotSame((string)$createDraftGroup->publishedGroupIdentifier, (string)$group->publishedGroupIdentifier());
        $this->assertSame((string)$newGroupIdentifier, (string)$group->publishedGroupIdentifier());
    }

    /**
     * 正常系：GroupNameのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetName(): void
    {
        $createDraftGroup = $this->createDummyDraftGroup();
        $group = $createDraftGroup->draftGroup;

        $this->assertSame((string)$createDraftGroup->name, (string)$group->name());

        $newName = new GroupName('aespa');
        $group->setName($newName);
        $this->assertNotSame((string)$createDraftGroup->name, (string)$group->name());
        $this->assertSame((string)$newName, (string)$group->name());
    }

    /**
     * 正常系：NormalizedNameのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetNormalizedName(): void
    {
        $createDraftGroup = $this->createDummyDraftGroup();
        $group = $createDraftGroup->draftGroup;

        $this->assertSame((string)$createDraftGroup->name, (string)$group->name());

        $newNormalizedName = 'aespa';
        $group->setNormalizedName($newNormalizedName);
        $this->assertNotSame($createDraftGroup->normalizedName, $group->normalizedName());
        $this->assertSame($newNormalizedName, $group->normalizedName());
    }

    /**
     * 正常系：AgencyIdentifierのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetAgencyIdentifier(): void
    {
        $createDraftGroup = $this->createDummyDraftGroup();
        $group = $createDraftGroup->draftGroup;

        $this->assertSame((string)$createDraftGroup->agencyIdentifier, (string)$group->agencyIdentifier());

        $newAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $group->setAgencyIdentifier($newAgencyIdentifier);
        $this->assertNotSame((string)$createDraftGroup->agencyIdentifier, (string)$group->agencyIdentifier());
        $this->assertSame((string)$newAgencyIdentifier, (string)$group->agencyIdentifier());
    }

    /**
     * 正常系：Descriptionのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetDescription(): void
    {
        $createDraftGroup = $this->createDummyDraftGroup();
        $group = $createDraftGroup->draftGroup;

        $this->assertSame((string)$createDraftGroup->description, (string)$group->description());

        $newDescription = new Description('### TWICE：風靡全球的九人女子團體
TWICE（트와이스）是在2015年透過韓國生存實境節目《SIXTEEN》所組成，隸屬於JYP娛樂旗下的九人女子團體。成員由五位韓國成員（娜璉、定延、志效、多賢、彩瑛）、三位日本成員（Momo、Sana、Mina）和一位台灣成員（子瑜）所組成，是一個聚集了多元魅力的多國籍團體。
團體名稱寓意為「用好的音樂感動一次，再用精彩的表演感動第二次」。如其名，自出道曲〈Like OOH-AHH〉以來，連續推出了〈CHEER UP〉、〈TT〉、〈LIKEY〉、〈What is Love?〉、〈FANCY〉等多首熱門歌曲。特別是〈TT〉中表現哭臉的「TT姿勢」，在日本更掀起社會現象，大受歡迎。
她們從出道初期的活潑可愛風格，隨著逐年成長，展現出從成熟帥氣到洗練的表演等多樣面貌。其特色是琅琅上口的旋律和易於模仿的舞蹈，獲得了各年齡層的支持。不僅在韓國和日本，她們也成功舉辦了世界級的體育場巡迴演唱會等，作為代表K-POP的頂級團體，至今仍然持續吸引著全世界的粉絲。官方粉絲名稱為「ONCE」。');
        $group->setDescription($newDescription);
        $this->assertNotSame((string)$createDraftGroup->description, (string)$group->description());
        $this->assertSame((string)$newDescription, (string)$group->description());
    }

    /**
     * 正常系：SongIdentifier[]のsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetSongIdentifiers(): void
    {
        $createDraftGroup = $this->createDummyDraftGroup();
        $group = $createDraftGroup->draftGroup;

        $this->assertSame($createDraftGroup->songIdentifiers, $group->songIdentifiers());

        $newSongIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUuid()),
            new SongIdentifier(StrTestHelper::generateUuid()),
        ];

        $group->setSongIdentifiers($newSongIdentifiers);
        $this->assertNotSame($createDraftGroup->songIdentifiers, $group->songIdentifiers());
        $this->assertSame($newSongIdentifiers, $group->songIdentifiers());
    }

    /**
     * 正常系：ImageLinkのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetImageLink(): void
    {
        $createDraftGroup = $this->createDummyDraftGroup();
        $group = $createDraftGroup->draftGroup;

        $this->assertSame((string)$createDraftGroup->imagePath, (string)$group->imagePath());

        $newImagePath = new ImagePath('/resources/public/images/after.webp');

        $group->setImagePath($newImagePath);
        $this->assertNotSame((string)$createDraftGroup->imagePath, (string)$group->imagePath());
        $this->assertSame((string)$newImagePath, (string)$group->imagePath());
    }

    /**
     * 正常系：Statusのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetStatus(): void
    {
        $createDraftGroup = $this->createDummyDraftGroup();
        $group = $createDraftGroup->draftGroup;

        $this->assertSame($createDraftGroup->status, $group->status());

        $newStatus = ApprovalStatus::Approved;

        $group->setStatus($newStatus);
        $this->assertNotSame($createDraftGroup->status, $group->status());
        $this->assertSame($newStatus, $group->status());
    }

    /**
     * ダミーのDraftGroupを作成するヘルパーメソッド
     *
     * @return DraftGroupTestData
     */
    private function createDummyDraftGroup(): DraftGroupTestData
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUuid()),
            new SongIdentifier(StrTestHelper::generateUuid()),
            new SongIdentifier(StrTestHelper::generateUuid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $status = ApprovalStatus::Pending;

        $draftGroup = new DraftGroup(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $status,
        );

        return new DraftGroupTestData(
            groupIdentifier: $groupIdentifier,
            publishedGroupIdentifier: $publishedGroupIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            editorIdentifier: $editorIdentifier,
            language: $language,
            name: $name,
            normalizedName: $normalizedName,
            agencyIdentifier: $agencyIdentifier,
            description: $description,
            songIdentifiers: $songIdentifiers,
            imagePath: $imagePath,
            status: $status,
            draftGroup: $draftGroup,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class DraftGroupTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param SongIdentifier[] $songIdentifiers
     */
    public function __construct(
        public GroupIdentifier          $groupIdentifier,
        public GroupIdentifier          $publishedGroupIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public EditorIdentifier         $editorIdentifier,
        public Language                 $language,
        public GroupName                $name,
        public string                   $normalizedName,
        public AgencyIdentifier         $agencyIdentifier,
        public Description              $description,
        public array                    $songIdentifiers,
        public ImagePath                $imagePath,
        public ApprovalStatus           $status,
        public DraftGroup               $draftGroup,
    ) {
    }
}
