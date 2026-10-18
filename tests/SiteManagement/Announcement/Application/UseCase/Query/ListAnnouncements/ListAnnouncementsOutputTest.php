<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements;

use DateTimeImmutable;
use Source\SiteManagement\Announcement\Application\UseCase\Query\AnnouncementReadModel;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsOutput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListAnnouncementsOutputTest extends TestCase
{
    /**
     * 正常系: Outputへも追加とtoArrayによる出力がうまく動作すること.
     *
     * @return void
     */
    public function testOutput(): void
    {
        $readModel1 = new AnnouncementReadModel(
            StrTestHelper::generateUuid(),
            'UPDATES',
            '🏆 あなたの一票が推しを輝かせる！新機能「グローバル投票」スタート！',
            'いつもk-poolをご利用いただき、ありがとうございます！
K-popを愛するすべてのファンの皆さまに、もっと「推し活」を楽しんでいただくための新機能、**「グローバル投票」**が本日よりスタートしました！🎉
## 「グローバル投票」でできること
「グローバル投票」は、あなたの"推し"を世界中のファンと一緒に応援できる、新しいリア...',
            new DateTimeImmutable(),
        );
        $readModel2 = new AnnouncementReadModel(
            StrTestHelper::generateUuid(),
            'NEWS',
            '🎬 推しの新しい魅力、見逃してない？メンバー関連動画リンク機能を追加！',
            'いつもk-poolをご利用いただき、ありがとうございます！
「このメンバーが活躍している動画だけを、まとめて見たい…！」
そんな多くのファンの皆さまの声にお応えして、各アイ...',
            new DateTimeImmutable('2005-02-01'),
        );
        $announcements = [$readModel1, $readModel2];
        $currentPage = 1;
        $lastPage = 2;
        $total = 3;
        $output = new ListAnnouncementsOutput();
        $output->output(
            $announcements,
            $currentPage,
            $lastPage,
            $total,
            2,
        );
        $this->assertSame([
            'announcements' => [$readModel1->toArray(), $readModel2->toArray()],
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'total' => $total,
            'per_page' => 2,
        ], $output->toArray());
    }
}
