<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Group;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\DebutDate;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\DisbandDate;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\Generation;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupStatus;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class GroupBasicTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $testData = $this->createDummyGroupBasic();

        $this->assertSame((string) $testData->name, (string) $testData->groupBasic->name());
        $this->assertSame($testData->normalizedName, $testData->groupBasic->normalizedName());
        $this->assertSame($testData->agencyIdentifier, $testData->groupBasic->agencyIdentifier());
        $this->assertSame($testData->groupType, $testData->groupBasic->groupType());
        $this->assertSame($testData->status, $testData->groupBasic->status());
        $this->assertSame($testData->generation, $testData->groupBasic->generation());
        $this->assertSame($testData->debutDate, $testData->groupBasic->debutDate());
        $this->assertSame($testData->disbandDate, $testData->groupBasic->disbandDate());
        $this->assertSame($testData->fandomName, $testData->groupBasic->fandomName());
        $this->assertSame($testData->officialColors, $testData->groupBasic->officialColors());
        $this->assertSame($testData->emoji, $testData->groupBasic->emoji());
        $this->assertSame($testData->representativeSymbol, $testData->groupBasic->representativeSymbol());
        $this->assertSame($testData->mainImageIdentifier, $testData->groupBasic->mainImageIdentifier());
    }

    /**
     * 正常系: nullable値をnullで生成できること
     *
     * @return void
     */
    public function test__constructWithNullValues(): void
    {
        $testData = $this->createDummyGroupBasic(
            withNullableValues: false,
        );

        $this->assertNull($testData->groupBasic->agencyIdentifier());
        $this->assertNull($testData->groupBasic->groupType());
        $this->assertNull($testData->groupBasic->status());
        $this->assertNull($testData->groupBasic->generation());
        $this->assertNull($testData->groupBasic->debutDate());
        $this->assertNull($testData->groupBasic->disbandDate());
        $this->assertEmpty($testData->groupBasic->officialColors());
        $this->assertNull($testData->groupBasic->mainImageIdentifier());
    }

    /**
     * 正常系: supportsResourceTypeがGROUPでtrueを返すこと
     *
     * @return void
     */
    public function testSupportsResourceTypeWithGroup(): void
    {
        $testData = $this->createDummyGroupBasic();

        $this->assertTrue($testData->groupBasic->supportsResourceType(ResourceType::GROUP));
    }

    /**
     * 正常系: supportsResourceTypeがGROUP以外でfalseを返すこと
     *
     * @return void
     */
    public function testSupportsResourceTypeWithOtherTypes(): void
    {
        $testData = $this->createDummyGroupBasic();

        $this->assertFalse($testData->groupBasic->supportsResourceType(ResourceType::AGENCY));
        $this->assertFalse($testData->groupBasic->supportsResourceType(ResourceType::TALENT));
        $this->assertFalse($testData->groupBasic->supportsResourceType(ResourceType::SONG));
    }

    /**
     * 正常系: getBasicTypeがgroupを返すこと
     *
     * @return void
     */
    public function testGetBasicType(): void
    {
        $testData = $this->createDummyGroupBasic();

        $this->assertSame('group', $testData->groupBasic->getBasicType());
    }

    /**
     * 正常系: normalizableKeysが正しいキーを返すこと
     *
     * @return void
     */
    public function testNormalizableKeys(): void
    {
        $testData = $this->createDummyGroupBasic();

        $this->assertSame([
            'name' => 'normalized_name',
        ], $testData->groupBasic->normalizableKeys());
    }

    /**
     * 正常系: toArrayが正しい配列を返すこと
     *
     * @return void
     */
    public function testToArray(): void
    {
        $testData = $this->createDummyGroupBasic();

        $array = $testData->groupBasic->toArray();

        $this->assertSame('group', $array['type']);
        $this->assertSame((string) $testData->name, $array['name']);
        $this->assertSame($testData->normalizedName, $array['normalized_name']);
        $this->assertSame((string) $testData->agencyIdentifier, $array['agency_identifier']);
        $this->assertSame($testData->groupType->value, $array['group_type']);
        $this->assertSame($testData->status->value, $array['status']);
        $this->assertSame($testData->generation->value, $array['generation']);
        $this->assertSame($testData->debutDate->format('Y-m-d'), $array['debut_date']);
        $this->assertSame($testData->disbandDate->format('Y-m-d'), $array['disband_date']);
        $this->assertSame($testData->fandomName->value(), $array['fandom_name']);
        $this->assertCount(count($testData->officialColors), $array['official_colors']);
        $this->assertSame($testData->emoji->value(), $array['emoji']);
        $this->assertSame($testData->representativeSymbol->value(), $array['representative_symbol']);
        $this->assertSame((string) $testData->mainImageIdentifier, $array['main_image_identifier']);
    }

    /**
     * 正常系: toArrayでnullable値がnullの場合
     *
     * @return void
     */
    public function testToArrayWithNullValues(): void
    {
        $testData = $this->createDummyGroupBasic(withNullableValues: false);

        $array = $testData->groupBasic->toArray();

        $this->assertNull($array['group_type']);
        $this->assertNull($array['status']);
        $this->assertNull($array['generation']);
        $this->assertNull($array['debut_date']);
        $this->assertNull($array['disband_date']);
        $this->assertEmpty($array['official_colors']);
        $this->assertNull($array['main_image_identifier']);
    }

    /**
     * 正常系: fromArrayで正しくインスタンスが生成されること
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $agencyUuid = StrTestHelper::generateUuid();
        $mainImageUuid = StrTestHelper::generateUuid();

        $data = [
            'name' => 'TWICE',
            'normalized_name' => 'twice',
            'agency_identifier' => $agencyUuid,
            'group_type' => 'girl_group',
            'status' => 'active',
            'generation' => '3rd',
            'debut_date' => '2015-10-20',
            'disband_date' => '2030-12-31',
            'fandom_name' => 'ONCE',
            'official_colors' => ['#FF5FA2', '#FFC0CB'],
            'emoji' => '🍭',
            'representative_symbol' => 'candy',
            'main_image_identifier' => $mainImageUuid,
        ];

        $groupBasic = GroupBasic::fromArray($data);

        $this->assertSame('TWICE', (string) $groupBasic->name());
        $this->assertSame('twice', $groupBasic->normalizedName());
        $this->assertSame($agencyUuid, (string) $groupBasic->agencyIdentifier());
        $this->assertSame(GroupType::GIRL_GROUP, $groupBasic->groupType());
        $this->assertSame(GroupStatus::ACTIVE, $groupBasic->status());
        $this->assertSame(Generation::THIRD, $groupBasic->generation());
        $this->assertSame('2015-10-20', $groupBasic->debutDate()->format('Y-m-d'));
        $this->assertSame('2030-12-31', $groupBasic->disbandDate()->format('Y-m-d'));
        $this->assertSame('ONCE', $groupBasic->fandomName()->value());
        $this->assertCount(2, $groupBasic->officialColors());
        $this->assertSame('#FF5FA2', (string) $groupBasic->officialColors()[0]);
        $this->assertSame('#FFC0CB', (string) $groupBasic->officialColors()[1]);
        $this->assertSame('🍭', $groupBasic->emoji()->value());
        $this->assertSame('candy', $groupBasic->representativeSymbol()->value());
        $this->assertSame($mainImageUuid, (string) $groupBasic->mainImageIdentifier());
    }

    /**
     * 正常系: fromArrayで最小限のデータで生成できること
     *
     * @return void
     */
    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'name' => 'TWICE',
            'agency_identifier' => null,
        ];

        $groupBasic = GroupBasic::fromArray($data);

        $this->assertSame('TWICE', (string) $groupBasic->name());
        $this->assertSame('', $groupBasic->normalizedName());
        $this->assertNull($groupBasic->agencyIdentifier());
        $this->assertNull($groupBasic->groupType());
        $this->assertNull($groupBasic->status());
        $this->assertNull($groupBasic->generation());
        $this->assertNull($groupBasic->debutDate());
        $this->assertNull($groupBasic->disbandDate());
        $this->assertSame('', $groupBasic->fandomName()->value());
        $this->assertEmpty($groupBasic->officialColors());
        $this->assertSame('', $groupBasic->emoji()->value());
        $this->assertSame('', $groupBasic->representativeSymbol()->value());
        $this->assertNull($groupBasic->mainImageIdentifier());
    }

    private function createDummyGroupBasic(
        bool $withNullableValues = true,
    ): GroupBasicTestData {
        $name = new Name('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = $withNullableValues ? new WikiIdentifier(StrTestHelper::generateUuid()) : null;
        $groupType = $withNullableValues ? GroupType::GIRL_GROUP : null;
        $status = $withNullableValues ? GroupStatus::ACTIVE : null;
        $generation = $withNullableValues ? Generation::THIRD : null;
        $debutDate = $withNullableValues ? new DebutDate(new DateTimeImmutable('2015-10-20')) : null;
        $disbandDate = $withNullableValues ? new DisbandDate(new DateTimeImmutable('2030-12-31')) : null;
        $fandomName = new FandomName('ONCE');
        $officialColors = $withNullableValues ? [
            new Color('#FF5FA2'),
            new Color('#FFC0CB'),
        ] : [];
        $emoji = new Emoji('🍭');
        $representativeSymbol = new RepresentativeSymbol('candy');
        $mainImageIdentifier = $withNullableValues ? new ImageIdentifier(StrTestHelper::generateUuid()) : null;

        $groupBasic = new GroupBasic(
            name: $name,
            normalizedName: $normalizedName,
            agencyIdentifier: $agencyIdentifier,
            groupType: $groupType,
            status: $status,
            generation: $generation,
            debutDate: $debutDate,
            disbandDate: $disbandDate,
            fandomName: $fandomName,
            officialColors: $officialColors,
            emoji: $emoji,
            representativeSymbol: $representativeSymbol,
            mainImageIdentifier: $mainImageIdentifier,
        );

        return new GroupBasicTestData(
            name: $name,
            normalizedName: $normalizedName,
            agencyIdentifier: $agencyIdentifier,
            groupType: $groupType,
            status: $status,
            generation: $generation,
            debutDate: $debutDate,
            disbandDate: $disbandDate,
            fandomName: $fandomName,
            officialColors: $officialColors,
            emoji: $emoji,
            representativeSymbol: $representativeSymbol,
            mainImageIdentifier: $mainImageIdentifier,
            groupBasic: $groupBasic,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class GroupBasicTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     *
     * @param array<Color> $officialColors
     */
    public function __construct(
        public Name $name,
        public string $normalizedName,
        public ?WikiIdentifier $agencyIdentifier,
        public ?GroupType $groupType,
        public ?GroupStatus $status,
        public ?Generation $generation,
        public ?DebutDate $debutDate,
        public ?DisbandDate $disbandDate,
        public FandomName $fandomName,
        public array $officialColors,
        public Emoji $emoji,
        public RepresentativeSymbol $representativeSymbol,
        public ?ImageIdentifier $mainImageIdentifier,
        public GroupBasic $groupBasic,
    ) {
    }
}
