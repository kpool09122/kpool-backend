<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftAgencyTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        $this->assertSame((string)$createDraftAgency->agencyIdentifier, (string)$agency->agencyIdentifier());
        $this->assertSame((string)$createDraftAgency->publishedAgencyIdentifier, (string)$agency->publishedAgencyIdentifier());
        $this->assertSame((string)$createDraftAgency->editorIdentifier, (string)$agency->editorIdentifier());
        $this->assertSame($createDraftAgency->language->value, $agency->language()->value);
        $this->assertSame((string)$createDraftAgency->name, (string)$agency->name());
        $this->assertSame($createDraftAgency->normalizedName, $agency->normalizedName());
        $this->assertSame((string)$createDraftAgency->ceo, (string)$agency->CEO());
        $this->assertSame($createDraftAgency->normalizedCEO, $agency->normalizedCEO());
        $this->assertSame($createDraftAgency->foundedIn->value(), $agency->foundedIn()->value());
        $this->assertSame((string)$createDraftAgency->description, (string)$agency->description());
        $this->assertSame($createDraftAgency->status->value, $agency->status()->value);
    }

    /**
     * 正常系：PublishedAgencyIdentifierのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetPublishedAgencyIdentifier(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        $this->assertSame((string)$createDraftAgency->publishedAgencyIdentifier, (string)$agency->publishedAgencyIdentifier());

        $newPublishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $agency->setPublishedAgencyIdentifier($newPublishedAgencyIdentifier);
        $this->assertNotSame((string)$createDraftAgency->publishedAgencyIdentifier, (string)$agency->publishedAgencyIdentifier());
        $this->assertSame((string)$newPublishedAgencyIdentifier, (string)$agency->publishedAgencyIdentifier());
    }

    /**
     * 正常系：AgencyNameのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetName(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        $this->assertSame((string)$createDraftAgency->name, (string)$agency->name());

        $newName = new AgencyName('HYBE');
        $agency->setName($newName);
        $this->assertNotSame((string)$createDraftAgency->name, (string)$agency->name());
        $this->assertSame((string)$newName, (string)$agency->name());
    }

    /**
     * 正常系：NormalizedNameのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetNormalizedName(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        $this->assertSame($createDraftAgency->normalizedName, $agency->normalizedName());

        $newNormalizedName = 'hybe';
        $agency->setNormalizedName($newNormalizedName);
        $this->assertNotSame($createDraftAgency->normalizedName, $agency->normalizedName());
        $this->assertSame($newNormalizedName, $agency->normalizedName());
    }

    /**
     * 正常系：CEOのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetCEO(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        $this->assertSame((string)$createDraftAgency->ceo, (string)$agency->CEO());

        $newCEO = new CEO('이재상');
        $agency->setCEO($newCEO);
        $this->assertNotSame((string)$createDraftAgency->ceo, (string)$agency->CEO());
        $this->assertSame((string)$newCEO, (string)$agency->CEO());
    }

    /**
     * 正常系：NormalizedCEOのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetNormalizedCEO(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        $this->assertSame($createDraftAgency->normalizedCEO, $agency->normalizedCEO());

        $newNormalizedCEO = 'ㅇㅈㅅ';
        $agency->setNormalizedCEO($newNormalizedCEO);
        $this->assertNotSame($createDraftAgency->normalizedCEO, $agency->normalizedCEO());
        $this->assertSame($newNormalizedCEO, $agency->normalizedCEO());
    }

    /**
     * 正常系：FoundedInのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetFoundedIn(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        $this->assertSame($createDraftAgency->foundedIn->value(), $agency->foundedIn()->value());

        $newFoundedIn = new FoundedIn(new DateTimeImmutable('2005-02-01'));
        $agency->setFoundedIn($newFoundedIn);
        $this->assertNotSame($createDraftAgency->foundedIn->value(), $agency->foundedIn()->value());
        $this->assertSame($newFoundedIn->value(), $agency->foundedIn()->value());
    }

    /**
     * 正常系：Descriptionのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetDescription(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        $this->assertSame((string)$createDraftAgency->description, (string)$agency->description());

        $newDescription = new Description('## HYBE Corporation 개요
HYBE의 가장 큰 특징은 단순한 연예 기획사가 아니라 **\'음악 산업의 혁신\'**을 목표로 하는 라이프스타일 플랫폼 기업이라는 점입니다. BTS의 세계적인 성공을 기반으로 2021년에 현재의 사명으로 변경했습니다.
비즈니스의 핵심은 여러 레이블이 독립적으로 음악을 제작하는 **\'멀티 레이블 체제\'**입니다. BIGHIT MUSIC (방탄소년단, 투모로우바이투게더), PLEDIS Entertainment (세븐틴), BELIFT LAB (엔하이픈), ADOR (뉴진스), KOZ ENTERTAINMENT (지코, 보이넥스트도어) 등 다수의 레이블을 산하에 두고 있습니다.
또한, 팬 커뮤니티 플랫폼인 **\'위버스(Weverse)\'**를 운영하는 등, 음악을 중심으로 하면서도 기술과 서비스를 융합한 독자적인 생태계를 구축하여 전 세계 음악 시장에 계속해서 영향을 미치고 있습니다.');
        $agency->setDescription($newDescription);
        $this->assertNotSame((string)$createDraftAgency->description, (string)$agency->description());
        $this->assertSame((string)$newDescription, (string)$agency->description());
    }

    /**
     * 正常系：Statusのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetStatus(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        $this->assertSame($createDraftAgency->status->value, $agency->status()->value);

        $newStatus = ApprovalStatus::UnderReview;
        $agency->setStatus($newStatus);
        $this->assertNotSame($createDraftAgency->status->value, $agency->status()->value);
        $this->assertSame($newStatus->value, $agency->status()->value);
    }

    /**
     * 正常系：MergerIdentifierのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMergerIdentifier(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        // 初期値はnull
        $this->assertNull($agency->mergerIdentifier());

        // 値を設定
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agency->setMergerIdentifier($mergerIdentifier);
        $this->assertSame($mergerIdentifier, $agency->mergerIdentifier());

        // nullを設定
        $agency->setMergerIdentifier(null);
        $this->assertNull($agency->mergerIdentifier());
    }

    /**
     * 正常系：MergedAtのsetterとgetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMergedAt(): void
    {
        $createDraftAgency = $this->createDummyDraftAgency();
        $agency = $createDraftAgency->draftAgency;

        // 初期値はnull
        $this->assertNull($agency->mergedAt());

        // 値を設定
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');
        $agency->setMergedAt($mergedAt);
        $this->assertSame($mergedAt, $agency->mergedAt());

        // nullを設定
        $agency->setMergedAt(null);
        $this->assertNull($agency->mergedAt());
    }

    /**
     * ダミーのDraftAgencyを作成するヘルパーメソッド
     *
     * @return DraftAgencyTestData
     */
    private function createDummyDraftAgency(): DraftAgencyTestData
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ';
        $ceo = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.
**\'진실, 성실, 겸손\'**이라는 가치관을 매우 중시하며, 소속 아티스트의 노래나 댄스 실력뿐만 아니라 인성을 존중하는 육성 방침으로 알려져 있습니다. 이러한 철학은 박진영이 오디션 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다.
음악적인 면에서는 설립자인 박진영이 직접 프로듀서로서 많은 곡 작업에 참여하여, 대중에게 사랑받는 캐치한 히트곡을 수많이 만들어왔습니다.
---
### 주요 소속 아티스트
지금까지 **원더걸스(Wonder Girls)**, **2PM**, **미쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출해왔습니다.
현재도
* **트와이스 (TWICE)**
* **스트레이 키즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.');
        $status = ApprovalStatus::Pending;

        $draftAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $ceo,
            $normalizedCEO,
            $foundedIn,
            $description,
            $status,
        );

        return new DraftAgencyTestData(
            agencyIdentifier: $agencyIdentifier,
            publishedAgencyIdentifier: $publishedAgencyIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            editorIdentifier: $editorIdentifier,
            language: $language,
            name: $name,
            normalizedName: $normalizedName,
            ceo: $ceo,
            normalizedCEO: $normalizedCEO,
            foundedIn: $foundedIn,
            description: $description,
            status: $status,
            draftAgency: $draftAgency,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class DraftAgencyTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public AgencyIdentifier          $agencyIdentifier,
        public AgencyIdentifier          $publishedAgencyIdentifier,
        public TranslationSetIdentifier  $translationSetIdentifier,
        public PrincipalIdentifier       $editorIdentifier,
        public Language                  $language,
        public AgencyName                $name,
        public string                    $normalizedName,
        public CEO                       $ceo,
        public string                    $normalizedCEO,
        public FoundedIn                 $foundedIn,
        public Description               $description,
        public ApprovalStatus            $status,
        public DraftAgency               $draftAgency,
    ) {
    }
}
