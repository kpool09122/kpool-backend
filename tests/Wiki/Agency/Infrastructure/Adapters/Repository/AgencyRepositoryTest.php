<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AgencyRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの事務所情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUlid();
        $translation = Translation::KOREAN;
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
        $version = 1;
        DB::table('agencies')->upsert([
            'id' => $id,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'translation' => $translation,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'version' => $version,
        ], 'id');
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agency = $agencyRepository->findById(
            new AgencyIdentifier($id),
        );
        $this->assertSame($id, (string)$agency->agencyIdentifier());
        $this->assertSame($translation, $agency->translation());
        $this->assertSame($name, (string)$agency->name());
        $this->assertSame($CEO, (string)$agency->CEO());
        $this->assertSame($founded_in->format('Y-m-d'), $agency->foundedIn()->value()->format('Y-m-d'));
        $this->assertSame($description, (string)$agency->description());
        $this->assertSame($version, $agency->version()->value());
    }

    /**
     * 正常系：指定したIDの事務所情報が存在しない場合、nullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindByIdWhenNoAgency(): void
    {
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agency = $agencyRepository->findById(
            new AgencyIdentifier(StrTestHelper::generateUlid()),
        );
        $this->assertNull($agency);
    }

    /**
     * 正常系：指定したIDの下書き情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindDraftById(): void
    {
        $id = StrTestHelper::generateUlid();
        $publishedId = StrTestHelper::generateUlid();
        $editorId = StrTestHelper::generateUlid();
        $translation = Translation::KOREAN;
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
        DB::table('draft_agencies')->upsert([
            'id' => $id,
            'published_id' => $publishedId,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'editor_id' => $editorId,
            'translation' => $translation,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'status' => $status->value,
        ], 'id');
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agency = $agencyRepository->findDraftById(
            new AgencyIdentifier($id),
        );
        $this->assertSame($id, (string)$agency->agencyIdentifier());
        $this->assertSame($publishedId, (string)$agency->publishedAgencyIdentifier());
        $this->assertSame($translation, $agency->translation());
        $this->assertSame($name, (string)$agency->name());
        $this->assertSame($CEO, (string)$agency->CEO());
        $this->assertSame($founded_in->format('Y-m-d'), $agency->foundedIn()->value()->format('Y-m-d'));
        $this->assertSame($description, (string)$agency->description());
        $this->assertSame($status, ApprovalStatus::Pending);
    }

    /**
     * 正常系：指定したIDの下書き情報が存在しない場合、nullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindDraftByIdWhenNoAgency(): void
    {
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agency = $agencyRepository->findDraftById(
            new AgencyIdentifier(StrTestHelper::generateUlid()),
        );
        $this->assertNull($agency);
    }

    /**
     * 正常系：正しく下書きを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSaveDraft(): void
    {
        $id = StrTestHelper::generateUlid();
        $publishedId = StrTestHelper::generateUlid();
        $editorId = StrTestHelper::generateUlid();
        $translation = Translation::KOREAN;
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
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier($editorId),
            $translation,
            new AgencyName($name),
            new CEO($CEO),
            new FoundedIn($founded_in),
            new Description($description),
            $status
        );
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agencyRepository->saveDraft(
            $agency,
        );

        $this->assertDatabaseHas('draft_agencies', [
            'id' => $id,
            'published_id' => $publishedId,
            'editor_id' => $editorId,
            'translation' => $translation,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'status' => $status->value,
        ]);
    }

    /**
     * 正常系：正しく下書きを削除できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testDelete(): void
    {
        $id = StrTestHelper::generateUlid();
        $publishedId = StrTestHelper::generateUlid();
        $editorId = StrTestHelper::generateUlid();
        $translation = Translation::KOREAN;
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
        DB::table('draft_agencies')->upsert([
            'id' => $id,
            'published_id' => $publishedId,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'editor_id' => $editorId,
            'translation' => $translation,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'status' => $status->value,
        ], 'id');

        $this->assertDatabaseHas('draft_agencies', [
            'id' => $id,
            'published_id' => $publishedId,
            'editor_id' => $editorId,
            'translation' => $translation,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'status' => $status->value,
        ]);

        $agency = new DraftAgency(
            new AgencyIdentifier($id),
            new AgencyIdentifier($publishedId),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier($editorId),
            $translation,
            new AgencyName($name),
            new CEO($CEO),
            new FoundedIn($founded_in),
            new Description($description),
            $status
        );
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agencyRepository->deleteDraft(
            $agency,
        );

        $this->assertDatabaseMissing('draft_agencies', [
            'id' => $id,
            'published_id' => $publishedId,
            'editor_id' => $editorId,
            'translation' => $translation,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'status' => $status->value,
        ]);
    }

    /**
     * 正常系：正しく事務所情報を保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSave(): void
    {
        $id = StrTestHelper::generateUlid();
        $translation = Translation::KOREAN;
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
        $version = 1;
        $agency = new Agency(
            new AgencyIdentifier($id),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $translation,
            new AgencyName($name),
            new CEO($CEO),
            new FoundedIn($founded_in),
            new Description($description),
            new Version($version),
        );
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agencyRepository->save(
            $agency,
        );

        $this->assertDatabaseHas('agencies', [
            'id' => $id,
            'translation' => $translation,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'version' => $version,
        ]);
    }

    /**
     * 正常系：同じ翻訳セットIDを持つ下書き情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindDraftsByTranslationSet(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());

        // 同じ翻訳セットの韓国語版
        $id1 = StrTestHelper::generateUlid();
        $publishedId1 = StrTestHelper::generateUlid();
        $editorId1 = StrTestHelper::generateUlid();
        $translation1 = Translation::KOREAN;
        $name1 = 'JYP엔터테인먼트';
        $CEO1 = 'J.Y. Park';
        $founded_in1 = new DateTimeImmutable('1997-04-25');
        $description1 = 'JYP엔터테인먼트에 대한 설명입니다.';
        $status1 = ApprovalStatus::Pending;

        DB::table('draft_agencies')->upsert([
            'id' => $id1,
            'published_id' => $publishedId1,
            'translation_set_identifier' => (string)$translationSetIdentifier,
            'editor_id' => $editorId1,
            'translation' => $translation1,
            'name' => $name1,
            'CEO' => $CEO1,
            'founded_in' => $founded_in1,
            'description' => $description1,
            'status' => $status1->value,
        ], 'id');

        // 同じ翻訳セットの日本語版
        $id2 = StrTestHelper::generateUlid();
        $publishedId2 = StrTestHelper::generateUlid();
        $editorId2 = StrTestHelper::generateUlid();
        $translation2 = Translation::JAPANESE;
        $name2 = 'JYPエンターテイメント';
        $CEO2 = 'J.Y. Park';
        $founded_in2 = new DateTimeImmutable('1997-04-25');
        $description2 = 'JYPエンターテイメントに関する説明です。';
        $status2 = ApprovalStatus::Approved;

        DB::table('draft_agencies')->upsert([
            'id' => $id2,
            'published_id' => $publishedId2,
            'translation_set_identifier' => (string)$translationSetIdentifier,
            'editor_id' => $editorId2,
            'translation' => $translation2,
            'name' => $name2,
            'CEO' => $CEO2,
            'founded_in' => $founded_in2,
            'description' => $description2,
            'status' => $status2->value,
        ], 'id');

        // 異なる翻訳セットのデータ（取得されないはず）
        $id3 = StrTestHelper::generateUlid();
        $publishedId3 = StrTestHelper::generateUlid();
        $editorId3 = StrTestHelper::generateUlid();
        $translation3 = Translation::KOREAN;
        $differentTranslationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());

        DB::table('draft_agencies')->upsert([
            'id' => $id3,
            'published_id' => $publishedId3,
            'translation_set_identifier' => (string)$differentTranslationSetIdentifier,
            'editor_id' => $editorId3,
            'translation' => $translation3,
            'name' => 'HYBE',
            'CEO' => '박지원',
            'founded_in' => new DateTimeImmutable('2005-02-01'),
            'description' => 'HYBEに関する説明です。',
            'status' => ApprovalStatus::Pending->value,
        ], 'id');

        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agencies = $agencyRepository->findDraftsByTranslationSet($translationSetIdentifier);

        // 2件取得できること
        $this->assertCount(2, $agencies);

        // 取得したデータの検証
        $agencyIds = array_map(fn ($agency) => (string)$agency->agencyIdentifier(), $agencies);
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
    public function testFindDraftsByTranslationSetWhenNoAgencies(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agencies = $agencyRepository->findDraftsByTranslationSet($translationSetIdentifier);

        $this->assertIsArray($agencies);
        $this->assertEmpty($agencies);
    }
}
