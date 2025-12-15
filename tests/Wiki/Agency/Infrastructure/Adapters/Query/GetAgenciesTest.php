<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Infrastructure\Adapters\Query;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesInput;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesInterface;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesOutput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Infrastructure\Adapters\Query\GetAgencies;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetAgenciesTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $getAgencies = $this->app->make(GetAgenciesInterface::class);
        $this->assertInstanceOf(GetAgencies::class, $getAgencies);
    }

    #[Group('useDb')]
    /**
     * 正常系：正しく事務所情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws \DateMalformedStringException
     */
    public function testProcess(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $language = Language::JAPANESE;
        $name = 'JYPエンターテイメント';
        $normalizedName = 'jypえんたーていめんと';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y park';
        $founded_in = new DateTimeImmutable('1997-04-25');
        $description = '歌手兼音楽プロデューサーの**パク・ジニョン(J.Y. Park)**が1997年に設立した韓国の大型総合エンターテイメント企業です。 HYBE、SM、YGエンターテインメントと共に韓国芸能界を率いる**\'BIG4\'**の一つに挙げられます。';
        $version = 1;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifier,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'version' => $version,
        ], 'id');
        $agencyIdentifer2 = new AgencyIdentifier(StrTestHelper::generateUlid());
        $name2 = 'HYBE';
        $normalizedName2 = 'hybe';
        $CEO2 = 'パク・ジウォン';
        $normalizedCEO2 = 'ぱく・じうぉん';
        $founded_in2 = new DateTimeImmutable('2005-02-01');
        $description2 = 'HYBEは、単なる芸能事務所にとどまらず、音楽を基盤とした多様な事業を展開する「エンターテインメントライフスタイルプラットフォーム企業」です。';
        $version2 = 2;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifer2,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language->value,
            'name' => $name2,
            'normalized_name' => $normalizedName2,
            'CEO' => $CEO2,
            'normalized_CEO' => $normalizedCEO2,
            'founded_in' => $founded_in2,
            'description' => $description2,
            'version' => $version2,
        ], 'id');

        $limit = 5;
        $order = 'name';
        $sort = 'asc';
        $searchWords = '';
        $input = new GetAgenciesInput(
            $limit,
            $order,
            $sort,
            $searchWords,
            $language,
        );
        $output = new GetAgenciesOutput();
        $getAgencies = $this->app->make(GetAgenciesInterface::class);
        $getAgencies->process($input, $output);

        $this->assertSame(2, $output->toArray()['total']);
        $this->assertSame(1, $output->toArray()['current_page']);
        $this->assertSame(1, $output->toArray()['last_page']);
        $this->assertSame($name2, $output->toArray()['agencies'][0]['name']);
        $this->assertSame($name, $output->toArray()['agencies'][1]['name']);
    }

    #[Group('useDb')]
    /**
     * 正常系：検索ワードの絞り込みがうまく機能すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws \DateMalformedStringException
     */
    public function testProcessUsingSearchWords(): void
    {
        $agencyIdentifer = new AgencyIdentifier(StrTestHelper::generateUlid());
        $language = Language::JAPANESE;
        $name = 'JYPエンターテイメント';
        $normalizedName = 'jypえんたーていめんと';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y park';
        $founded_in = new DateTimeImmutable('1997-04-25');
        $description = '歌手兼音楽プロデューサーの**パク・ジニョン(J.Y. Park)**が1997年に設立した韓国の大型総合エンターテイメント企業です。 HYBE、SM、YGエンターテインメントと共に韓国芸能界を率いる**\'BIG4\'**の一つに挙げられます。';
        $version = 1;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifer,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'version' => $version,
        ], 'id');
        $agencyIdentifer2 = new AgencyIdentifier(StrTestHelper::generateUlid());
        $name2 = 'HYBE';
        $normalizedName2 = 'hybe';
        $CEO2 = 'パク・ジウォン';
        $normalizedCEO2 = 'ぱく・じうぉん';
        $founded_in2 = new DateTimeImmutable('2005-02-01');
        $description2 = 'HYBEは、単なる芸能事務所にとどまらず、音楽を基盤とした多様な事業を展開する「エンターテインメントライフスタイルプラットフォーム企業」です。';
        $version2 = 2;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifer2,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language->value,
            'name' => $name2,
            'normalized_name' => $normalizedName2,
            'CEO' => $CEO2,
            'normalized_CEO' => $normalizedCEO2,
            'founded_in' => $founded_in2,
            'description' => $description2,
            'version' => $version2,
        ], 'id');

        $limit = 5;
        $order = 'name';
        $sort = 'asc';
        $searchWords = 'エンターテイメント';
        $input = new GetAgenciesInput(
            $limit,
            $order,
            $sort,
            $searchWords,
            $language,
        );
        $output = new GetAgenciesOutput();
        $getAgencies = $this->app->make(GetAgenciesInterface::class);
        $getAgencies->process($input, $output);

        $this->assertSame(1, $output->toArray()['total']);
        $this->assertSame(1, $output->toArray()['current_page']);
        $this->assertSame(1, $output->toArray()['last_page']);
        $this->assertSame($name, $output->toArray()['agencies'][0]['name']);
    }

    #[Group('useDb')]
    /**
     * 正常系：表示数の上限機能がうまく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws \DateMalformedStringException
     */
    public function testProcessWithLimit(): void
    {
        $agencyIdentifer = new AgencyIdentifier(StrTestHelper::generateUlid());
        $language = Language::JAPANESE;
        $name = 'JYPエンターテイメント';
        $normalizedName = 'jypえんたーていめんと';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y. park';
        $founded_in = new DateTimeImmutable('1997-04-25');
        $description = '歌手兼音楽プロデューサーの**パク・ジニョン(J.Y. Park)**が1997年に設立した韓国の大型総合エンターテイメント企業です。 HYBE、SM、YGエンターテインメントと共に韓国芸能界を率いる**\'BIG4\'**の一つに挙げられます。';
        $version = 1;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifer,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'version' => $version,
        ], 'id');
        $agencyIdentifer2 = new AgencyIdentifier(StrTestHelper::generateUlid());
        $name2 = 'HYBE';
        $normalizedName2 = 'hybe';
        $CEO2 = 'パク・ジウォン';
        $normalizedCEO2 = 'ぱく・じうぉん';
        $founded_in2 = new DateTimeImmutable('2005-02-01');
        $description2 = 'HYBEは、単なる芸能事務所にとどまらず、音楽を基盤とした多様な事業を展開する「エンターテインメントライフスタイルプラットフォーム企業」です。';
        $version2 = 2;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifer2,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language->value,
            'name' => $name2,
            'normalized_name' => $normalizedName2,
            'CEO' => $CEO2,
            'normalized_CEO' => $normalizedCEO2,
            'founded_in' => $founded_in2,
            'description' => $description2,
            'version' => $version2,
        ], 'id');

        $limit = 1;
        $order = 'name';
        $sort = 'asc';
        $searchWords = '';
        $input = new GetAgenciesInput(
            $limit,
            $order,
            $sort,
            $searchWords,
            $language,
        );
        $output = new GetAgenciesOutput();
        $getAgencies = $this->app->make(GetAgenciesInterface::class);
        $getAgencies->process($input, $output);

        $this->assertSame(2, $output->toArray()['total']);
        $this->assertSame(1, $output->toArray()['current_page']);
        $this->assertSame(2, $output->toArray()['last_page']);
        $this->assertCount(1, $output->toArray()['agencies']);
    }

    #[Group('useDb')]
    /**
     * 正常系：ソート機能が正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws \DateMalformedStringException
     */
    public function testProcessUsingSort(): void
    {
        $agencyIdentifer = new AgencyIdentifier(StrTestHelper::generateUlid());
        $language = Language::JAPANESE;
        $name = 'JYPエンターテイメント';
        $normalizedName = 'jypえんたーていめんと';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y. park';
        $founded_in = new DateTimeImmutable('1997-04-25');
        $description = '歌手兼音楽プロデューサーの**パク・ジニョン(J.Y. Park)**が1997年に設立した韓国の大型総合エンターテイメント企業です。 HYBE、SM、YGエンターテインメントと共に韓国芸能界を率いる**\'BIG4\'**の一つに挙げられます。';
        $version = 1;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifer,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'version' => $version,
        ], 'id');
        $agencyIdentifer2 = new AgencyIdentifier(StrTestHelper::generateUlid());
        $name2 = 'HYBE';
        $normalizedName2 = 'hybe';
        $CEO2 = 'パク・ジウォン';
        $normalizedCEO2 = 'ぱく・じうぉん';
        $founded_in2 = new DateTimeImmutable('2005-02-01');
        $description2 = 'HYBEは、単なる芸能事務所にとどまらず、音楽を基盤とした多様な事業を展開する「エンターテインメントライフスタイルプラットフォーム企業」です。';
        $version2 = 2;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifer2,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language->value,
            'name' => $name2,
            'normalized_name' => $normalizedName2,
            'CEO' => $CEO2,
            'normalized_CEO' => $normalizedCEO2,
            'founded_in' => $founded_in2,
            'description' => $description2,
            'version' => $version2,
        ], 'id');

        $limit = 5;
        $order = 'name';
        $sort = 'asc';
        $searchWords = '';
        $input = new GetAgenciesInput(
            $limit,
            $order,
            $sort,
            $searchWords,
            $language,
        );
        $output = new GetAgenciesOutput();
        $getAgencies = $this->app->make(GetAgenciesInterface::class);
        $getAgencies->process($input, $output);
        $this->assertSame($name, $output->toArray()['agencies'][1]['name']);

        $limit2 = 5;
        $order2 = 'name';
        $sort2 = 'desc';
        $searchWords2 = '';
        $input = new GetAgenciesInput(
            $limit2,
            $order2,
            $sort2,
            $searchWords2,
            $language,
        );
        $output = new GetAgenciesOutput();
        $getAgencies = $this->app->make(GetAgenciesInterface::class);
        $getAgencies->process($input, $output);
        $this->assertSame($name, $output->toArray()['agencies'][0]['name']);
    }

    #[Group('useDb')]
    /**
     * 正常系：言語の違うレコードは取得されないこと.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws \DateMalformedStringException
     */
    public function testProcessWhenDifferentTranslation(): void
    {
        $agencyIdentifer = new AgencyIdentifier(StrTestHelper::generateUlid());
        $language = Language::JAPANESE;
        $name = 'JYPエンターテイメント';
        $normalizedName = 'jypえんたーていめんと';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y. park';
        $founded_in = new DateTimeImmutable('1997-04-25');
        $description = '歌手兼音楽プロデューサーの**パク・ジニョン(J.Y. Park)**が1997年に設立した韓国の大型総合エンターテイメント企業です。 HYBE、SM、YGエンターテインメントと共に韓国芸能界を率いる**\'BIG4\'**の一つに挙げられます。';
        $version = 1;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifer,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $founded_in,
            'description' => $description,
            'version' => $version,
        ], 'id');
        $agencyIdentifer2 = new AgencyIdentifier(StrTestHelper::generateUlid());
        $language2 = Language::KOREAN;
        $name2 = 'HYBE';
        $normalizedName2 = 'hybe';
        $CEO2 = '박지원';
        $normalizedCEO2 = 'ㅂㅈㅇ';
        $founded_in2 = new DateTimeImmutable('2005-02-01');
        $description2 = 'HYBE는 단순한 연예 기획사를 넘어, 음악을 기반으로 한 다양한 사업을 전개하는 \'엔터테인먼트 라이프스타일 플랫폼 기업\'입니다.';
        $version2 = 2;
        DB::table('agencies')->upsert([
            'id' => (string)$agencyIdentifer2,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => $language2->value,
            'name' => $name2,
            'normalized_name' => $normalizedName2,
            'CEO' => $CEO2,
            'normalized_CEO' => $normalizedCEO2,
            'founded_in' => $founded_in2,
            'description' => $description2,
            'version' => $version2,
        ], 'id');

        $limit = 5;
        $order = 'name';
        $sort = 'asc';
        $searchWords = '';
        $input = new GetAgenciesInput(
            $limit,
            $order,
            $sort,
            $searchWords,
            $language,
        );
        $output = new GetAgenciesOutput();
        $getAgencies = $this->app->make(GetAgenciesInterface::class);
        $getAgencies->process($input, $output);
        $this->assertSame(1, $output->toArray()['total']);
        $this->assertSame(1, $output->toArray()['current_page']);
        $this->assertSame(1, $output->toArray()['last_page']);
        $this->assertSame($name, $output->toArray()['agencies'][0]['name']);
    }
}
