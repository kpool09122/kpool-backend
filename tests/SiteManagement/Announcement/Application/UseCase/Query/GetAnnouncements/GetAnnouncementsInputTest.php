<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncements;

use Source\Shared\Domain\ValueObject\Translation;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncements\GetAnnouncementsInput;
use Tests\TestCase;

class GetAnnouncementsInputTest extends TestCase
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
        $translation = Translation::JAPANESE;
        $input = new GetAnnouncementsInput(
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
