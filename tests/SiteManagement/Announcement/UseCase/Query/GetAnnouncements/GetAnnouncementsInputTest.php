<?php

namespace Tests\SiteManagement\Announcement\UseCase\Query\GetAnnouncements;

use Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncements\GetAnnouncementsInput;
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
        $input = new GetAnnouncementsInput(
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
