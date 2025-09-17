<?php

namespace Tests\Wiki\Group\UseCase\Query\GetGroups;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Group\UseCase\Query\GetGroups\GetGroupsInput;
use Tests\TestCase;

class GetGroupsInputTest extends TestCase
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
        $translation = Translation::KOREAN;
        $input = new GetGroupsInput(
            $limit,
            $order,
            $sort,
            $searchWords,
            $translation,
        );
        $this->assertSame($limit, $input->limit());
        $this->assertSame($order, $input->order());
        $this->assertSame($sort, $input->sort());
        $this->assertSame($searchWords, $input->searchWords());
        $this->assertSame($translation->value, $input->translation()->value);
    }
}
