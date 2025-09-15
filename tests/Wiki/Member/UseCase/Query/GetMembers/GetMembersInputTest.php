<?php

namespace Tests\Wiki\Member\UseCase\Query\GetMembers;

use Businesses\Wiki\Member\UseCase\Query\GetMembers\GetMembersInput;
use Tests\TestCase;

class GetMembersInputTest extends TestCase
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
        $input = new GetMembersInput(
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
