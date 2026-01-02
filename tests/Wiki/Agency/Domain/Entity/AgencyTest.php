<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AgencyTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $createAgency = $this->createDummyAgency();
        $agency = $createAgency->agency;

        $this->assertSame((string)$createAgency->agencyIdentifier, (string)$agency->agencyIdentifier());
        $this->assertSame($createAgency->translation->value, $agency->language()->value);
        $this->assertSame((string)$createAgency->name, (string)$agency->name());
        $this->assertSame($createAgency->normalizedName, $agency->normalizedName());
        $this->assertSame((string)$createAgency->CEO, (string)$agency->CEO());
        $this->assertSame($createAgency->normalizedCEO, $agency->normalizedCEO());
        $this->assertSame($createAgency->foundedIn->value(), $agency->foundedIn()->value());
        $this->assertSame((string)$createAgency->description, (string)$agency->description());
        $this->assertSame($createAgency->version->value(), $agency->version()->value());
    }

    /**
     * 正常系：AgencyNameのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetName(): void
    {
        $createAgency = $this->createDummyAgency();
        $agency = $createAgency->agency;

        $this->assertSame((string)$createAgency->name, (string)$agency->name());

        $newName = new AgencyName('HYBE');
        $agency->setName($newName);
        $this->assertNotSame((string)$createAgency->name, (string)$agency->name());
        $this->assertSame((string)$newName, (string)$agency->name());
    }

    /**
     * 正常系：NormalizedNameのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetNormalizedName(): void
    {
        $createAgency = $this->createDummyAgency();
        $agency = $createAgency->agency;

        $this->assertSame($createAgency->normalizedName, $agency->normalizedName());

        $newNormalizedName = 'hybe';
        $agency->setNormalizedName($newNormalizedName);
        $this->assertNotSame($createAgency->normalizedName, $agency->normalizedName());
        $this->assertSame($newNormalizedName, $agency->normalizedName());
    }

    /**
     * 正常系：CEOのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetCEO(): void
    {
        $createAgency = $this->createDummyAgency();
        $agency = $createAgency->agency;

        $this->assertSame((string)$createAgency->CEO, (string)$agency->CEO());

        $newCEO = new CEO('이재상');
        $agency->setCEO($newCEO);
        $this->assertNotSame((string)$createAgency->CEO, (string)$agency->CEO());
        $this->assertSame((string)$newCEO, (string)$agency->CEO());
    }

    /**
     * 正常系：NormalizedCEOのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetNormalizedCEO(): void
    {
        $createAgency = $this->createDummyAgency();
        $agency = $createAgency->agency;

        $this->assertSame($createAgency->normalizedCEO, $agency->normalizedCEO());

        $newNormalizedCEO = 'ㅇㅈㅅ';
        $agency->setNormalizedCEO($newNormalizedCEO);
        $this->assertNotSame($createAgency->normalizedCEO, $agency->normalizedCEO());
        $this->assertSame($newNormalizedCEO, $agency->normalizedCEO());
    }

    /**
     * 正常系：FoundedInのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetFoundedIn(): void
    {
        $createAgency = $this->createDummyAgency();
        $agency = $createAgency->agency;

        $this->assertSame($createAgency->foundedIn->value(), $agency->foundedIn()->value());

        $newFoundedIn = new FoundedIn(new DateTimeImmutable('2005-02-01'));
        $agency->setFoundedIn($newFoundedIn);
        $this->assertNotSame($createAgency->foundedIn->value(), $agency->foundedIn()->value());
        $this->assertSame($newFoundedIn->value(), $agency->foundedIn()->value());
    }

    /**
     * 正常系：Descriptionのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetDescription(): void
    {
        $createAgency = $this->createDummyAgency();
        $agency = $createAgency->agency;

        $this->assertSame((string)$createAgency->description, (string)$agency->description());

        $newDescription = new Description(
            <<<'DESCRIPTION'
## HYBE Corporation 개요
HYBE의 가장 큰 특징은 단순한 연예 기획사가 아니라 **'음악 산업의 혁신'**을 목표로 하는 라이프스타일 플랫폼 기업이라는 점입니다. BTS의 세계적인 성공을 기반으로 2021년에 현재의 사명으로 변경했습니다.
비즈니스의 핵심은 여러 레이블이 독립적으로 음악을 제작하는 **'멀티 레이블 체제'**입니다. BIGHIT MUSIC (방탄소년단, 투모로우바이투게더), PLEDIS Entertainment (세븐틴), BELIFT LAB (엔하이픈), ADOR (뉴진스), KOZ ENTERTAINMENT (지코, 보이넥스트도어) 등 다수의 레이블을 산하에 두고 있습니다.
또한, 팬 커뮤니티 플랫폼인 **'위버스(Weverse)'**를 운영하는 등, 음악을 중심으로 하면서도 기술과 서비스를 융합한 독자적인 생태계를 구축하여 전 세계 음악 시장에 계속해서 영향을 미치고 있습니다.
DESCRIPTION
        );
        $agency->setDescription($newDescription);
        $this->assertNotSame((string)$createAgency->description, (string)$agency->description());
        $this->assertSame((string)$newDescription, (string)$agency->description());
    }

    /**
     * 正常系：updateVersionが正しく動作すること.
     *
     * @return void
     */
    public function testUpdateVersion(): void
    {
        $createAgency = $this->createDummyAgency();
        $agency = $createAgency->agency;

        $this->assertSame($createAgency->version->value(), $agency->version()->value());

        $agency->updateVersion();

        $this->assertNotSame($createAgency->version->value(), $agency->version()->value());
        $this->assertSame($createAgency->version->value() + 1, $agency->version()->value());
    }

    /**
     * 正常系：hasSameVersionが正しく動作すること.
     *
     * @return void
     */
    public function testHasSameVersion(): void
    {
        $createAgency = $this->createDummyAgency();
        $agency = $createAgency->agency;

        // 同じバージョン
        $sameVersion = new Version(1);
        $this->assertTrue($agency->hasSameVersion($sameVersion));

        // 異なるバージョン
        $differentVersion = new Version(2);
        $this->assertFalse($agency->hasSameVersion($differentVersion));
    }

    /**
     * 正常系：isVersionGreaterThanが正しく動作すること.
     *
     * @return void
     */
    public function testIsVersionGreaterThan(): void
    {
        // バージョン5のAgencyを作成
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            Language::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            'jypㅇㅌㅌㅇㅁㅌ',
            new CEO('J.Y. Park'),
            'j.y.park',
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('Description'),
            new Version(5),
        );

        // 現在のバージョンより小さいバージョン
        $smallerVersion = new Version(3);
        $this->assertTrue($agency->isVersionGreaterThan($smallerVersion));

        // 現在のバージョンと同じバージョン
        $sameVersion = new Version(5);
        $this->assertFalse($agency->isVersionGreaterThan($sameVersion));

        // 現在のバージョンより大きいバージョン
        $largerVersion = new Version(7);
        $this->assertFalse($agency->isVersionGreaterThan($largerVersion));
    }

    /**
     * ダミーのAgencyを作成するヘルパーメソッド
     *
     * @return AgencyTestData
     */
    private function createDummyAgency(): AgencyTestData
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $translation = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.yp.park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description(
            <<<'DESCRIPTION'
### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **'BIG4'** 중 하나로 꼽힙니다.
**'진실, 성실, 겸손'**이라는 가치관을 매우 중시하며, 소속 아티스트의 노래나 댄스 실력뿐만 아니라 인성을 존중하는 육성 방침으로 알려져 있습니다. 이러한 철학은 박진영이 오디션 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다.
음악적인 면에서는 설립자인 박진영이 직접 프로듀서로서 많은 곡 작업에 참여하여, 대중에게 사랑받는 캐치한 히트곡을 수많이 만들어왔습니다.
---
### 주요 소속 아티스트
지금까지 **원더걸스(Wonder Girls)**, **2PM**, **미쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출해왔습니다.
현재도
* **트와이스 (TWICE)**
* **스트레이 키즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.
DESCRIPTION
        );
        $version = new Version(1);
        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
            $description,
            $version,
        );

        return new AgencyTestData(
            agencyIdentifier: $agencyIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            translation: $translation,
            name: $name,
            normalizedName: $normalizedName,
            CEO: $CEO,
            normalizedCEO: $normalizedCEO,
            foundedIn: $foundedIn,
            description: $description,
            version: $version,
            agency: $agency,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class AgencyTestData
{
    public function __construct(
        public AgencyIdentifier         $agencyIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Language                 $translation,
        public AgencyName               $name,
        public string                   $normalizedName,
        public CEO                      $CEO,
        public string                   $normalizedCEO,
        public FoundedIn                $foundedIn,
        public Description              $description,
        public Version                  $version,
        public Agency                   $agency,
    ) {
    }
}
