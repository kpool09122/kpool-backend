<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Query\GetSongs;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Song\Application\UseCase\Query\GetSongs\GetSongsInput;
use Tests\TestCase;

class GetSongsInputTest extends TestCase
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
        $input = new GetSongsInput(
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
