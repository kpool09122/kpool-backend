<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Birthday;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\BloodType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\EnglishLevel;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Height;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\MBTI;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Position;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\ZodiacSign;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class TalentBasicTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $testData = $this->createDummyTalentBasic();

        $this->assertSame((string) $testData->name, (string) $testData->talentBasic->name());
        $this->assertSame($testData->normalizedName, $testData->talentBasic->normalizedName());
        $this->assertSame((string) $testData->realName, (string) $testData->talentBasic->realName());
        $this->assertSame($testData->normalizedRealName, $testData->talentBasic->normalizedRealName());
        $this->assertSame($testData->birthday, $testData->talentBasic->birthday());
        $this->assertSame($testData->agencyIdentifier, $testData->talentBasic->agencyIdentifier());
        $this->assertSame($testData->groupIdentifiers, $testData->talentBasic->groupIdentifiers());
        $this->assertSame($testData->emoji, $testData->talentBasic->emoji());
        $this->assertSame($testData->representativeSymbol, $testData->talentBasic->representativeSymbol());
        $this->assertSame($testData->position, $testData->talentBasic->position());
        $this->assertSame($testData->mbti, $testData->talentBasic->mbti());
        $this->assertSame($testData->zodiacSign, $testData->talentBasic->zodiacSign());
        $this->assertSame($testData->englishLevel, $testData->talentBasic->englishLevel());
        $this->assertSame($testData->height, $testData->talentBasic->height());
        $this->assertSame($testData->bloodType, $testData->talentBasic->bloodType());
        $this->assertSame($testData->fandomName, $testData->talentBasic->fandomName());
        $this->assertSame($testData->profileImageIdentifier, $testData->talentBasic->profileImageIdentifier());
    }

    /**
     * 正常系: nullable値をnullで生成できること
     *
     * @return void
     */
    public function test__constructWithNullValues(): void
    {
        $testData = $this->createDummyTalentBasic(
            withNullableValues: false,
        );

        $this->assertNull($testData->talentBasic->birthday());
        $this->assertNull($testData->talentBasic->agencyIdentifier());
        $this->assertEmpty($testData->talentBasic->groupIdentifiers());
        $this->assertNull($testData->talentBasic->mbti());
        $this->assertNull($testData->talentBasic->zodiacSign());
        $this->assertNull($testData->talentBasic->englishLevel());
        $this->assertNull($testData->talentBasic->height());
        $this->assertNull($testData->talentBasic->bloodType());
        $this->assertNull($testData->talentBasic->profileImageIdentifier());
    }

    /**
     * 正常系: supportsResourceTypeがTALENTでtrueを返すこと
     *
     * @return void
     */
    public function testSupportsResourceTypeWithTalent(): void
    {
        $testData = $this->createDummyTalentBasic();

        $this->assertTrue($testData->talentBasic->supportsResourceType(ResourceType::TALENT));
    }

    /**
     * 正常系: supportsResourceTypeがTALENT以外でfalseを返すこと
     *
     * @return void
     */
    public function testSupportsResourceTypeWithOtherTypes(): void
    {
        $testData = $this->createDummyTalentBasic();

        $this->assertFalse($testData->talentBasic->supportsResourceType(ResourceType::AGENCY));
        $this->assertFalse($testData->talentBasic->supportsResourceType(ResourceType::GROUP));
        $this->assertFalse($testData->talentBasic->supportsResourceType(ResourceType::SONG));
    }

    /**
     * 正常系: getBasicTypeがtalentを返すこと
     *
     * @return void
     */
    public function testGetBasicType(): void
    {
        $testData = $this->createDummyTalentBasic();

        $this->assertSame('talent', $testData->talentBasic->getBasicType());
    }

    /**
     * 正常系: normalizableKeysが正しいキーを返すこと
     *
     * @return void
     */
    public function testNormalizableKeys(): void
    {
        $testData = $this->createDummyTalentBasic();

        $this->assertSame([
            'name' => 'normalized_name',
            'real_name' => 'normalized_real_name',
        ], $testData->talentBasic->normalizableKeys());
    }

    /**
     * 正常系: toArrayが正しい配列を返すこと
     *
     * @return void
     */
    public function testToArray(): void
    {
        $testData = $this->createDummyTalentBasic();

        $array = $testData->talentBasic->toArray();

        $this->assertSame('talent', $array['type']);
        $this->assertSame((string) $testData->name, $array['name']);
        $this->assertSame($testData->normalizedName, $array['normalized_name']);
        $this->assertSame($testData->emoji->value(), $array['emoji']);
        $this->assertSame($testData->representativeSymbol->value(), $array['representative_symbol']);
        $this->assertSame($testData->position->value(), $array['position']);
        $this->assertSame($testData->mbti->value, $array['mbti']);
        $this->assertSame($testData->zodiacSign->value, $array['zodiac_sign']);
        $this->assertSame($testData->englishLevel->value, $array['english_level']);
        $this->assertSame($testData->height->centimeters(), $array['height']);
        $this->assertSame($testData->bloodType->value, $array['blood_type']);
        $this->assertSame($testData->fandomName->value(), $array['fandom_name']);
        $this->assertSame((string) $testData->agencyIdentifier, $array['agency_identifier']);
        $this->assertCount(count($testData->groupIdentifiers), $array['group_identifiers']);
        $this->assertSame((string) $testData->profileImageIdentifier, $array['profile_image_identifier']);
    }

    /**
     * 正常系: toArrayでnullable値がnullの場合
     *
     * @return void
     */
    public function testToArrayWithNullValues(): void
    {
        $testData = $this->createDummyTalentBasic(withNullableValues: false);

        $array = $testData->talentBasic->toArray();

        $this->assertNull($array['birthday']);
        $this->assertNull($array['mbti']);
        $this->assertNull($array['zodiac_sign']);
        $this->assertNull($array['english_level']);
        $this->assertNull($array['height']);
        $this->assertNull($array['blood_type']);
        $this->assertNull($array['profile_image_identifier']);
    }

    /**
     * 正常系: fromArrayで正しくインスタンスが生成されること
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $agencyUuid = StrTestHelper::generateUuid();
        $groupUuid1 = StrTestHelper::generateUuid();
        $groupUuid2 = StrTestHelper::generateUuid();
        $profileImageUuid = StrTestHelper::generateUuid();

        $data = [
            'name' => '채영',
            'normalized_name' => 'chaeyoung',
            'real_name' => '손채영',
            'normalized_real_name' => 'sonchaeyoung',
            'birthday' => null,
            'agency_identifier' => $agencyUuid,
            'group_identifiers' => [$groupUuid1, $groupUuid2],
            'emoji' => '🍓',
            'representative_symbol' => 'strawberry',
            'position' => 'Main Rapper, Sub Vocalist',
            'mbti' => 'INFP',
            'zodiac_sign' => 'taurus',
            'english_level' => 'conversational',
            'height' => 159,
            'blood_type' => 'B',
            'fandom_name' => 'ONCE',
            'profile_image_identifier' => $profileImageUuid,
        ];

        $talentBasic = TalentBasic::fromArray($data);

        $this->assertSame('채영', (string) $talentBasic->name());
        $this->assertSame('chaeyoung', $talentBasic->normalizedName());
        $this->assertSame('손채영', (string) $talentBasic->realName());
        $this->assertSame('sonchaeyoung', $talentBasic->normalizedRealName());
        $this->assertSame('🍓', $talentBasic->emoji()->value());
        $this->assertSame('strawberry', $talentBasic->representativeSymbol()->value());
        $this->assertSame('Main Rapper, Sub Vocalist', $talentBasic->position()->value());
        $this->assertSame(MBTI::INFP, $talentBasic->mbti());
        $this->assertSame(ZodiacSign::TAURUS, $talentBasic->zodiacSign());
        $this->assertSame(EnglishLevel::CONVERSATIONAL, $talentBasic->englishLevel());
        $this->assertSame(159, $talentBasic->height()->centimeters());
        $this->assertSame(BloodType::B, $talentBasic->bloodType());
        $this->assertSame('ONCE', $talentBasic->fandomName()->value());
        $this->assertSame($agencyUuid, (string) $talentBasic->agencyIdentifier());
        $this->assertCount(2, $talentBasic->groupIdentifiers());
        $this->assertSame($profileImageUuid, (string) $talentBasic->profileImageIdentifier());
    }

    /**
     * 正常系: fromArrayで最小限のデータで生成できること
     *
     * @return void
     */
    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'name' => '채영',
            'agency_identifier' => null,
            'group_identifiers' => null,
        ];

        $talentBasic = TalentBasic::fromArray($data);

        $this->assertSame('채영', (string) $talentBasic->name());
        $this->assertSame('', $talentBasic->normalizedName());
        $this->assertNull($talentBasic->agencyIdentifier());
        $this->assertEmpty($talentBasic->groupIdentifiers());
        $this->assertSame('', $talentBasic->emoji()->value());
        $this->assertSame('', $talentBasic->representativeSymbol()->value());
        $this->assertSame('', $talentBasic->position()->value());
        $this->assertNull($talentBasic->mbti());
        $this->assertNull($talentBasic->zodiacSign());
        $this->assertNull($talentBasic->englishLevel());
        $this->assertNull($talentBasic->height());
        $this->assertNull($talentBasic->bloodType());
        $this->assertSame('', $talentBasic->fandomName()->value());
        $this->assertNull($talentBasic->profileImageIdentifier());
    }

    private function createDummyTalentBasic(
        bool $withNullableValues = true,
    ): TalentBasicTestData {
        $name = new Name('채영');
        $normalizedName = 'chaeyoung';
        $realName = new RealName('손채영');
        $normalizedRealName = 'sonchaeyoung';
        $birthday = $withNullableValues ? new Birthday(new DateTimeImmutable('1999-04-23')) : null;
        $agencyIdentifier = $withNullableValues ? new WikiIdentifier(StrTestHelper::generateUuid()) : null;
        $groupIdentifiers = $withNullableValues ? [
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new WikiIdentifier(StrTestHelper::generateUuid()),
        ] : [];
        $emoji = new Emoji('🍓');
        $representativeSymbol = new RepresentativeSymbol('strawberry');
        $position = new Position('Main Rapper, Sub Vocalist');
        $mbti = $withNullableValues ? MBTI::INFP : null;
        $zodiacSign = $withNullableValues ? ZodiacSign::TAURUS : null;
        $englishLevel = $withNullableValues ? EnglishLevel::CONVERSATIONAL : null;
        $height = $withNullableValues ? new Height(159) : null;
        $bloodType = $withNullableValues ? BloodType::B : null;
        $fandomName = new FandomName('ONCE');
        $profileImageIdentifier = $withNullableValues ? new ImageIdentifier(StrTestHelper::generateUuid()) : null;

        $talentBasic = new TalentBasic(
            name: $name,
            normalizedName: $normalizedName,
            realName: $realName,
            normalizedRealName: $normalizedRealName,
            birthday: $birthday,
            agencyIdentifier: $agencyIdentifier,
            groupIdentifiers: $groupIdentifiers,
            emoji: $emoji,
            representativeSymbol: $representativeSymbol,
            position: $position,
            mbti: $mbti,
            zodiacSign: $zodiacSign,
            englishLevel: $englishLevel,
            height: $height,
            bloodType: $bloodType,
            fandomName: $fandomName,
            profileImageIdentifier: $profileImageIdentifier,
        );

        return new TalentBasicTestData(
            name: $name,
            normalizedName: $normalizedName,
            realName: $realName,
            normalizedRealName: $normalizedRealName,
            birthday: $birthday,
            agencyIdentifier: $agencyIdentifier,
            groupIdentifiers: $groupIdentifiers,
            emoji: $emoji,
            representativeSymbol: $representativeSymbol,
            position: $position,
            mbti: $mbti,
            zodiacSign: $zodiacSign,
            englishLevel: $englishLevel,
            height: $height,
            bloodType: $bloodType,
            fandomName: $fandomName,
            profileImageIdentifier: $profileImageIdentifier,
            talentBasic: $talentBasic,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class TalentBasicTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     *
     * @param WikiIdentifier[] $groupIdentifiers
     */
    public function __construct(
        public Name $name,
        public string $normalizedName,
        public RealName $realName,
        public string $normalizedRealName,
        public ?Birthday $birthday,
        public ?WikiIdentifier $agencyIdentifier,
        public array $groupIdentifiers,
        public Emoji $emoji,
        public RepresentativeSymbol $representativeSymbol,
        public Position $position,
        public ?MBTI $mbti,
        public ?ZodiacSign $zodiacSign,
        public ?EnglishLevel $englishLevel,
        public ?Height $height,
        public ?BloodType $bloodType,
        public FandomName $fandomName,
        public ?ImageIdentifier $profileImageIdentifier,
        public TalentBasic $talentBasic,
    ) {
    }
}
