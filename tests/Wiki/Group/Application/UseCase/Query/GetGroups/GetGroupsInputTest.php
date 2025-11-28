<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Query\GetGroups;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\UseCase\Query\GetGroups\GetGroupsInput;
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
        $language = Language::KOREAN;
        $input = new GetGroupsInput(
            $limit,
            $order,
            $sort,
            $searchWords,
            $language,
        );
        $this->assertSame($limit, $input->limit());
        $this->assertSame($order, $input->order());
        $this->assertSame($sort, $input->sort());
        $this->assertSame($searchWords, $input->searchWords());
        $this->assertSame($language->value, $input->language()->value);
    }
}
