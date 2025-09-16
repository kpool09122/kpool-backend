<?php

namespace Tests\Wiki\Agency\UseCase\Query\GetAgencies;

use Businesses\Wiki\Agency\UseCase\Query\GetAgencies\GetAgenciesInput;
use Tests\TestCase;

class GetAgenciesInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $limit = 10;
        $order = 'id';
        $sort = 'desc';
        $searchWords = 'test';
        $input = new GetAgenciesInput(
            $limit,
            $order,
            $sort,
            $searchWords,
        );
        $this->assertSame($limit, $input->limit());
        $this->assertSame($order, $input->order());
        $this->assertSame($sort, $input->sort());
        $this->assertSame($searchWords, $input->searchWords());
    }
}
