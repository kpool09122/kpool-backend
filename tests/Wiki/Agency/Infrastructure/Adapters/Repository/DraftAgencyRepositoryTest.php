<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Tests\Helper\CreateDraftAgency;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftAgencyRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの下書き情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUuid();
        $publishedId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $approverId = StrTestHelper::generateUuid();
        $mergerId = StrTestHelper::generateUuid();
        $language = Language::KOREAN;
        $name = 'JYP엔터테인먼트';
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y. park';
        $foundedIn = '1997-04-25';
        $description = '### JYP엔터테인먼트 (JYP Entertainment)
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
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.';
        $status = ApprovalStatus::Pending;

        CreateDraftAgency::create($id, [
            'published_id' => $publishedId,
            'translation_set_identifier' => $translationSetIdentifier,
            'slug' => 'jyp-entertainment',
            'editor_id' => $editorId,
            'language' => $language->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $foundedIn,
            'description' => $description,
            'status' => $status->value,
            'approver_id' => $approverId,
            'merger_id' => $mergerId,
        ]);

        $agencyRepository = $this->app->make(DraftAgencyRepositoryInterface::class);
        $agency = $agencyRepository->findById(
            new AgencyIdentifier($id),
        );
        $this->assertSame($id, (string)$agency->agencyIdentifier());
        $this->assertSame($publishedId, (string)$agency->publishedAgencyIdentifier());
        $this->assertSame($language, $agency->language());
        $this->assertSame($name, (string)$agency->name());
        $this->assertSame($CEO, (string)$agency->CEO());
        $this->assertSame($foundedIn, $agency->foundedIn()->value()->format('Y-m-d'));
        $this->assertSame($description, (string)$agency->description());
        $this->assertSame($status, $agency->status());
        $this->assertSame($approverId, (string)$agency->approverIdentifier());
        $this->assertSame($mergerId, (string)$agency->mergerIdentifier());
    }

    /**
     * 正常系：指定したIDの下書き情報が存在しない場合、nullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDraftByIdWhenNoAgency(): void
    {
        $agencyRepository = $this->app->make(DraftAgencyRepositoryInterface::class);
        $agency = $agencyRepository->findById(
            new AgencyIdentifier(StrTestHelper::generateUuid()),
        );
        $this->assertNull($agency);
    }

    /**
     * 正常系：正しく下書きを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveDraft(): void
    {
        $id = StrTestHelper::generateUuid();
        $publishedId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $approverId = StrTestHelper::generateUuid();
        $mergerId = StrTestHelper::generateUuid();
        $language = Language::KOREAN;
        $name = 'JYP엔터테인먼트';
        $CEO = 'J.Y. Park';
        $founded_in = new DateTimeImmutable('1997-04-25');
        $description = '### JYP엔터테인먼트 (JYP Entertainment)
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
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.';
        $status = ApprovalStatus::Pending;
        $agency = new DraftAgency(
            new AgencyIdentifier($id),
            new AgencyIdentifier($publishedId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-slug'),
            new PrincipalIdentifier($editorId),
            $language,
            new AgencyName($name),
            'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ',
            new CEO($CEO),
            'j.y. park',
            new FoundedIn($founded_in),
            new Description($description),
            $status,
            new PrincipalIdentifier($approverId),
            new PrincipalIdentifier($mergerId),
        );
        $agencyRepository = $this->app->make(DraftAgencyRepositoryInterface::class);
        $agencyRepository->save(
            $agency,
        );

        $this->assertDatabaseHas('draft_agencies', [
            'id' => $id,
            'published_id' => $publishedId,
            'editor_id' => $editorId,
            'language' => $language,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'status' => $status->value,
            'approver_id' => $approverId,
            'merger_id' => $mergerId,
        ]);
    }

    /**
     * 正常系：正しく下書きを削除できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $id = StrTestHelper::generateUuid();
        $publishedId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $language = Language::KOREAN;
        $name = 'JYP엔터테인먼트';
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y. park';
        $foundedIn = '1997-04-25';
        $description = '### JYP엔터테인먼트 (JYP Entertainment)
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
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.';
        $status = ApprovalStatus::Pending;

        CreateDraftAgency::create($id, [
            'published_id' => $publishedId,
            'translation_set_identifier' => $translationSetIdentifier,
            'slug' => 'jyp-entertainment',
            'editor_id' => $editorId,
            'language' => $language->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $foundedIn,
            'description' => $description,
            'status' => $status->value,
        ]);

        $this->assertDatabaseHas('draft_agencies', [
            'id' => $id,
            'published_id' => $publishedId,
            'editor_id' => $editorId,
            'language' => $language,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $foundedIn,
            'description' => $description,
            'status' => $status->value,
        ]);

        $agency = new DraftAgency(
            new AgencyIdentifier($id),
            new AgencyIdentifier($publishedId),
            new TranslationSetIdentifier($translationSetIdentifier),
            new Slug('test-slug'),
            new PrincipalIdentifier($editorId),
            $language,
            new AgencyName($name),
            'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ',
            new CEO($CEO),
            'j.y. park',
            new FoundedIn(new DateTimeImmutable($foundedIn)),
            new Description($description),
            $status
        );
        $agencyRepository = $this->app->make(DraftAgencyRepositoryInterface::class);
        $agencyRepository->delete(
            $agency,
        );

        $this->assertDatabaseMissing('draft_agencies', [
            'id' => $id,
            'published_id' => $publishedId,
            'editor_id' => $editorId,
            'language' => $language,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $foundedIn,
            'description' => $description,
            'status' => $status->value,
        ]);
    }

    /**
     * 正常系：同じ翻訳セットIDを持つ下書き情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSet(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        // 同じ翻訳セットの韓国語版
        $id1 = StrTestHelper::generateUuid();
        $publishedId1 = StrTestHelper::generateUuid();
        $editorId1 = StrTestHelper::generateUuid();

        CreateDraftAgency::create($id1, [
            'published_id' => $publishedId1,
            'translation_set_identifier' => (string)$translationSetIdentifier,
            'slug' => 'jyp-entertainment',
            'editor_id' => $editorId1,
            'language' => Language::KOREAN->value,
            'name' => 'JYP엔터테인먼트',
            'normalized_name' => 'jypㅇㅌㅌㅇㅁㅌ',
            'CEO' => 'J.Y. Park',
            'normalized_CEO' => 'j.y. park',
            'founded_in' => '1997-04-25',
            'description' => 'JYP엔터테인먼트에 대한 설명입니다.',
            'status' => ApprovalStatus::Pending->value,
        ]);

        // 同じ翻訳セットの日本語版
        $id2 = StrTestHelper::generateUuid();
        $publishedId2 = StrTestHelper::generateUuid();
        $editorId2 = StrTestHelper::generateUuid();

        CreateDraftAgency::create($id2, [
            'published_id' => $publishedId2,
            'translation_set_identifier' => (string)$translationSetIdentifier,
            'slug' => 'jyp-entertainment',
            'editor_id' => $editorId2,
            'language' => Language::JAPANESE->value,
            'name' => 'JYPエンターテイメント',
            'normalized_name' => 'jypえんたーていめんと',
            'CEO' => 'J.Y. Park',
            'normalized_CEO' => 'j.y. park',
            'founded_in' => '1997-04-25',
            'description' => 'JYPエンターテイメントに関する説明です。',
            'status' => ApprovalStatus::Approved->value,
        ]);

        // 異なる翻訳セットのデータ（取得されないはず）
        $id3 = StrTestHelper::generateUuid();
        $publishedId3 = StrTestHelper::generateUuid();
        $editorId3 = StrTestHelper::generateUuid();
        $differentTranslationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        CreateDraftAgency::create($id3, [
            'published_id' => $publishedId3,
            'translation_set_identifier' => (string)$differentTranslationSetIdentifier,
            'slug' => 'hybe',
            'editor_id' => $editorId3,
            'language' => Language::KOREAN->value,
            'name' => 'HYBE',
            'normalized_name' => 'hybe',
            'CEO' => '박지원',
            'normalized_CEO' => 'ㅂㅈㅇ',
            'founded_in' => '2005-02-01',
            'description' => 'HYBEに関する説明です。',
            'status' => ApprovalStatus::Pending->value,
        ]);

        $agencyRepository = $this->app->make(DraftAgencyRepositoryInterface::class);
        $agencies = $agencyRepository->findByTranslationSet($translationSetIdentifier);

        // 2件取得できること
        $this->assertCount(2, $agencies);

        // 取得したデータの検証
        $agencyIds = array_map(static fn ($agency) => (string)$agency->agencyIdentifier(), $agencies);
        $this->assertContains($id1, $agencyIds);
        $this->assertContains($id2, $agencyIds);
        $this->assertNotContains($id3, $agencyIds);

        // 各エージェンシーの翻訳セットIDが一致していること
        foreach ($agencies as $agency) {
            $this->assertSame((string)$translationSetIdentifier, (string)$agency->translationSetIdentifier());
        }
    }

    /**
     * 正常系：該当する翻訳セットIDの下書き情報が存在しない場合、空の配列が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetWhenNoAgencies(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyRepository = $this->app->make(DraftAgencyRepositoryInterface::class);
        $agencies = $agencyRepository->findByTranslationSet($translationSetIdentifier);

        $this->assertIsArray($agencies);
        $this->assertEmpty($agencies);
    }
}
