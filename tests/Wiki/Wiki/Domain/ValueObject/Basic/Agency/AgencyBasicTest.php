<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Agency;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyStatus;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\FoundedIn;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class AgencyBasicTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $testData = $this->createDummyAgencyBasic();

        $this->assertSame((string) $testData->name, (string) $testData->agencyBasic->name());
        $this->assertSame($testData->normalizedName, $testData->agencyBasic->normalizedName());
        $this->assertSame((string) $testData->ceo, (string) $testData->agencyBasic->ceo());
        $this->assertSame($testData->normalizedCeo, $testData->agencyBasic->normalizedCeo());
        $this->assertSame($testData->foundedIn, $testData->agencyBasic->foundedIn());
        $this->assertSame($testData->parentAgencyIdentifier, $testData->agencyBasic->parentAgencyIdentifier());
        $this->assertSame($testData->status, $testData->agencyBasic->status());
        $this->assertSame($testData->logoImageIdentifier, $testData->agencyBasic->logoImageIdentifier());
        $this->assertSame($testData->officialWebsite, $testData->agencyBasic->officialWebsite());
        $this->assertSame($testData->socialLinks, $testData->agencyBasic->socialLinks());
    }

    /**
     * 正常系: nullable値をnullで生成できること
     *
     * @return void
     */
    public function test__constructWithNullValues(): void
    {
        $testData = $this->createDummyAgencyBasic(
            withNullableValues: false,
        );

        $this->assertNull($testData->agencyBasic->foundedIn());
        $this->assertNull($testData->agencyBasic->parentAgencyIdentifier());
        $this->assertNull($testData->agencyBasic->status());
        $this->assertNull($testData->agencyBasic->logoImageIdentifier());
        $this->assertNull($testData->agencyBasic->officialWebsite());
        $this->assertEmpty($testData->agencyBasic->socialLinks());
    }

    /**
     * 正常系: supportsResourceTypeがAGENCYでtrueを返すこと
     *
     * @return void
     */
    public function testSupportsResourceTypeWithAgency(): void
    {
        $testData = $this->createDummyAgencyBasic();

        $this->assertTrue($testData->agencyBasic->supportsResourceType(ResourceType::AGENCY));
    }

    /**
     * 正常系: supportsResourceTypeがAGENCY以外でfalseを返すこと
     *
     * @return void
     */
    public function testSupportsResourceTypeWithOtherTypes(): void
    {
        $testData = $this->createDummyAgencyBasic();

        $this->assertFalse($testData->agencyBasic->supportsResourceType(ResourceType::GROUP));
        $this->assertFalse($testData->agencyBasic->supportsResourceType(ResourceType::TALENT));
        $this->assertFalse($testData->agencyBasic->supportsResourceType(ResourceType::SONG));
    }

    /**
     * 正常系: getBasicTypeがagencyを返すこと
     *
     * @return void
     */
    public function testGetBasicType(): void
    {
        $testData = $this->createDummyAgencyBasic();

        $this->assertSame('agency', $testData->agencyBasic->getBasicType());
    }

    /**
     * 正常系: normalizableKeysが正しいキーを返すこと
     *
     * @return void
     */
    public function testNormalizableKeys(): void
    {
        $testData = $this->createDummyAgencyBasic();

        $this->assertSame([
            'name' => 'normalized_name',
            'ceo' => 'normalized_ceo',
        ], $testData->agencyBasic->normalizableKeys());
    }

    /**
     * 正常系: toArrayが正しい配列を返すこと
     *
     * @return void
     */
    public function testToArray(): void
    {
        $testData = $this->createDummyAgencyBasic();

        $array = $testData->agencyBasic->toArray();

        $this->assertSame('agency', $array['type']);
        $this->assertSame((string) $testData->name, $array['name']);
        $this->assertSame($testData->normalizedName, $array['normalized_name']);
        $this->assertSame((string) $testData->ceo, $array['ceo']);
        $this->assertSame($testData->normalizedCeo, $array['normalized_ceo']);
        $this->assertSame($testData->foundedIn->format('Y-m-d'), $array['founded_in']);
        $this->assertSame((string) $testData->parentAgencyIdentifier, $array['parent_agency_identifier']);
        $this->assertSame($testData->status->value, $array['status']);
        $this->assertSame((string) $testData->logoImageIdentifier, $array['logo_image_identifier']);
        $this->assertSame((string) $testData->officialWebsite, $array['official_website']);
        $this->assertCount(count($testData->socialLinks), $array['social_links']);
    }

    /**
     * 正常系: toArrayでnullable値がnullの場合
     *
     * @return void
     */
    public function testToArrayWithNullValues(): void
    {
        $testData = $this->createDummyAgencyBasic(withNullableValues: false);

        $array = $testData->agencyBasic->toArray();

        $this->assertNull($array['founded_in']);
        $this->assertNull($array['parent_agency_identifier']);
        $this->assertNull($array['status']);
        $this->assertNull($array['logo_image_identifier']);
        $this->assertNull($array['official_website']);
        $this->assertEmpty($array['social_links']);
    }

    /**
     * 正常系: fromArrayで正しくインスタンスが生成されること
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $parentAgencyUuid = StrTestHelper::generateUuid();
        $logoImageUuid = StrTestHelper::generateUuid();

        $data = [
            'name' => 'Big Hit Music',
            'normalized_name' => 'bighitmusic',
            'ceo' => '신영재',
            'normalized_ceo' => 'shinyoungjae',
            'founded_in' => '2005-02-01',
            'parent_agency_identifier' => $parentAgencyUuid,
            'status' => 'active',
            'logo_image_identifier' => $logoImageUuid,
            'official_website' => 'https://www.bighitmusic.com',
            'social_links' => [
                'https://twitter.com/BIGHIT_MUSIC',
                'https://www.instagram.com/bighit_music/',
            ],
        ];

        $agencyBasic = AgencyBasic::fromArray($data);

        $this->assertSame('Big Hit Music', (string) $agencyBasic->name());
        $this->assertSame('bighitmusic', $agencyBasic->normalizedName());
        $this->assertSame('신영재', (string) $agencyBasic->ceo());
        $this->assertSame('shinyoungjae', $agencyBasic->normalizedCeo());
        $this->assertSame('2005-02-01', $agencyBasic->foundedIn()->format('Y-m-d'));
        $this->assertSame($parentAgencyUuid, (string) $agencyBasic->parentAgencyIdentifier());
        $this->assertSame(AgencyStatus::ACTIVE, $agencyBasic->status());
        $this->assertSame($logoImageUuid, (string) $agencyBasic->logoImageIdentifier());
        $this->assertSame('https://www.bighitmusic.com', (string) $agencyBasic->officialWebsite());
        $this->assertCount(2, $agencyBasic->socialLinks());
        $this->assertSame('https://twitter.com/BIGHIT_MUSIC', (string) $agencyBasic->socialLinks()[0]);
        $this->assertSame('https://www.instagram.com/bighit_music/', (string) $agencyBasic->socialLinks()[1]);
    }

    /**
     * 正常系: fromArrayで最小限のデータで生成できること
     *
     * @return void
     */
    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'name' => 'HYBE',
        ];

        $agencyBasic = AgencyBasic::fromArray($data);

        $this->assertSame('HYBE', (string) $agencyBasic->name());
        $this->assertSame('', $agencyBasic->normalizedName());
        $this->assertSame('', (string) $agencyBasic->ceo());
        $this->assertSame('', $agencyBasic->normalizedCeo());
        $this->assertNull($agencyBasic->foundedIn());
        $this->assertNull($agencyBasic->parentAgencyIdentifier());
        $this->assertNull($agencyBasic->status());
        $this->assertNull($agencyBasic->logoImageIdentifier());
        $this->assertNull($agencyBasic->officialWebsite());
        $this->assertEmpty($agencyBasic->socialLinks());
    }

    private function createDummyAgencyBasic(
        bool $withNullableValues = true,
    ): AgencyBasicTestData {
        $name = new Name('Big Hit Music');
        $normalizedName = 'bighitmusic';
        $ceo = new CEO('신영재');
        $normalizedCeo = 'shinyoungjae';
        $foundedIn = $withNullableValues ? new FoundedIn(new DateTimeImmutable('2005-02-01')) : null;
        $parentAgencyIdentifier = $withNullableValues ? new WikiIdentifier(StrTestHelper::generateUuid()) : null;
        $status = $withNullableValues ? AgencyStatus::ACTIVE : null;
        $logoImageIdentifier = $withNullableValues ? new ImageIdentifier(StrTestHelper::generateUuid()) : null;
        $officialWebsite = $withNullableValues ? new ExternalContentLink('https://www.bighitmusic.com') : null;
        $socialLinks = $withNullableValues ? [
            new ExternalContentLink('https://twitter.com/BIGHIT_MUSIC'),
            new ExternalContentLink('https://www.instagram.com/bighit_music/'),
        ] : [];

        $agencyBasic = new AgencyBasic(
            name: $name,
            normalizedName: $normalizedName,
            ceo: $ceo,
            normalizedCeo: $normalizedCeo,
            foundedIn: $foundedIn,
            parentAgencyIdentifier: $parentAgencyIdentifier,
            status: $status,
            logoImageIdentifier: $logoImageIdentifier,
            officialWebsite: $officialWebsite,
            socialLinks: $socialLinks,
        );

        return new AgencyBasicTestData(
            name: $name,
            normalizedName: $normalizedName,
            ceo: $ceo,
            normalizedCeo: $normalizedCeo,
            foundedIn: $foundedIn,
            parentAgencyIdentifier: $parentAgencyIdentifier,
            status: $status,
            logoImageIdentifier: $logoImageIdentifier,
            officialWebsite: $officialWebsite,
            socialLinks: $socialLinks,
            agencyBasic: $agencyBasic,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class AgencyBasicTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     *
     * @param array<ExternalContentLink> $socialLinks
     */
    public function __construct(
        public Name $name,
        public string $normalizedName,
        public CEO $ceo,
        public string $normalizedCeo,
        public ?FoundedIn $foundedIn,
        public ?WikiIdentifier $parentAgencyIdentifier,
        public ?AgencyStatus $status,
        public ?ImageIdentifier $logoImageIdentifier,
        public ?ExternalContentLink $officialWebsite,
        public array $socialLinks,
        public AgencyBasic $agencyBasic,
    ) {
    }
}
