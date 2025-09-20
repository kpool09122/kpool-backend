<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Query\GetAgencies;

use DateTimeImmutable;
use Source\Wiki\Agency\Application\UseCase\Query\AgencyReadModel;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesOutput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetAgenciesOutputTest extends TestCase
{
    /**
     * 正常系: Outputへも追加とtoArrayによる出力がうまく動作すること.
     *
     * @return void
     */
    public function testOutput(): void
    {
        $readModel1 = new AgencyReadModel(
            StrTestHelper::generateUlid(),
            'JYPエンターテイメント',
            'J.Y. Park',
            new DateTimeImmutable('1997-04-25'),
            '歌手兼音楽プロデューサーの**パク・ジニョン(J.Y. Park)**が1997年に設立した韓国の大型総合エンターテイメント企業です。 HYBE、SM、YGエンターテインメントと共に韓国芸能界を率いる**\'BIG4\'**の一つに挙げられます。',
        );
        $readModel2 = new AgencyReadModel(
            StrTestHelper::generateUlid(),
            'HYBE',
            'パク・ジウォン',
            new DateTimeImmutable('2005-02-01'),
            'HYBEは、単なる芸能事務所にとどまらず、音楽を基盤とした多様な事業を展開する「エンターテインメントライフスタイルプラットフォーム企業」です。',
        );
        $agencies = [$readModel1, $readModel2];
        $currentPage = 1;
        $lastPage = 2;
        $total = 3;
        $output = new GetAgenciesOutput();
        $output->output(
            $agencies,
            $currentPage,
            $lastPage,
            $total,
        );
        $this->assertSame([
            'agencies' => [$readModel1->toArray(), $readModel2->toArray()],
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'total' => $total,
        ], $output->toArray());
    }
}
