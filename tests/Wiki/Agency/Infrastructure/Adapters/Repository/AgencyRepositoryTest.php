<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AgencyRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの事務所情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUuid();
        $translation = Language::KOREAN;
        $name = 'JYP엔터테인먼트';
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y. park';
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
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'language' => $translation,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'version' => $version,
        ], 'id');
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agency = $agencyRepository->findById(
            new AgencyIdentifier($id),
        );
        $this->assertSame($id, (string)$agency->agencyIdentifier());
        $this->assertSame($translation, $agency->language());
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
    #[Group('useDb')]
    public function testFindByIdWhenNoAgency(): void
    {
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agency = $agencyRepository->findById(
            new AgencyIdentifier(StrTestHelper::generateUuid()),
        );
        $this->assertNull($agency);
    }

    /**
     * 正常系：正しく事務所情報を保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $id = StrTestHelper::generateUuid();
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
        $version = 1;
        $agency = new Agency(
            new AgencyIdentifier($id),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            $language,
            new AgencyName($name),
            'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ',
            new CEO($CEO),
            'j.y. park',
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
            'language' => $language,
            'name' => $name,
            'CEO' => $CEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'version' => $version,
        ]);
    }

    /**
     * 正常系：翻訳セットIDで複数のAgencyを取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifier(): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $idKo = StrTestHelper::generateUuid();
        $idJa = StrTestHelper::generateUuid();

        // 韓国語版Agency
        DB::table('agencies')->upsert([
            'id' => $idKo,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => 'JYP엔터테인먼트',
            'normalized_name' => 'jypㅇㅌㅌㅇㅁㅌ',
            'CEO' => 'J.Y. Park',
            'normalized_CEO' => 'j.y. park',
            'founded_in' => new DateTimeImmutable('1997-04-25'),
            'description' => 'Korean description',
            'version' => 3,
        ], 'id');

        // 日本語版Agency
        DB::table('agencies')->upsert([
            'id' => $idJa,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::JAPANESE->value,
            'name' => 'JYPエンターテインメント',
            'normalized_name' => 'jypえんたーていんめんと',
            'CEO' => 'J.Y. パク',
            'normalized_CEO' => 'j.y. ぱく',
            'founded_in' => new DateTimeImmutable('1997-04-25'),
            'description' => 'Japanese description',
            'version' => 3,
        ], 'id');

        // 別の翻訳セットのAgency（取得されないはず）
        $otherId = StrTestHelper::generateUuid();
        DB::table('agencies')->upsert([
            'id' => $otherId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'language' => Language::KOREAN->value,
            'name' => 'SM엔터테인먼트',
            'normalized_name' => 'smㅇㅌㅌㅇㅁㅌ',
            'CEO' => 'Lee Sung-su',
            'normalized_CEO' => 'lee sung-su',
            'founded_in' => new DateTimeImmutable('1995-02-14'),
            'description' => 'SM description',
            'version' => 1,
        ], 'id');

        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agencies = $agencyRepository->findByTranslationSetIdentifier(
            new TranslationSetIdentifier($translationSetIdentifier)
        );

        $this->assertCount(2, $agencies);

        $agencyIds = array_map(fn (Agency $a) => (string) $a->agencyIdentifier(), $agencies);
        $this->assertContains($idKo, $agencyIds);
        $this->assertContains($idJa, $agencyIds);
        $this->assertNotContains($otherId, $agencyIds);
    }

    /**
     * 正常系：該当するAgencyが存在しない場合、空の配列が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierWhenNoAgencies(): void
    {
        $agencyRepository = $this->app->make(AgencyRepositoryInterface::class);
        $agencies = $agencyRepository->findByTranslationSetIdentifier(
            new TranslationSetIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertIsArray($agencies);
        $this->assertEmpty($agencies);
    }
}
