<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Query\GetAgencies;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesInput;
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
        $translation = Language::KOREAN;
        $input = new GetAgenciesInput(
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
        $this->assertSame($translation->value, $input->language()->value);
    }
}
