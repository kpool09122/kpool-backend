<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\UseCase\Query\GetGroups;

use Businesses\Wiki\Group\UseCase\Query\GetGroups\GetGroupsOutput;
use Businesses\Wiki\Group\UseCase\Query\GroupReadModel;
use Businesses\Wiki\Group\UseCase\Query\SongReadModel;
use DateTimeImmutable;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetGroupsOutputTest extends TestCase
{
    /**
     * 正常系: Outputへも追加とtoArrayによる出力がうまく動作すること.
     *
     * @return void
     */
    public function testOutput(): void
    {
        $songReadModel1 = new SongReadModel(
            StrTestHelper::generateUlid(),
            'TT',
            new DateTimeImmutable('2016-10-24'),
            'https://example1.youtube.com/watch?v=dQw4w9WgXcQ',
            '/resources/public/images/test1.webp',
        );
        $songReadModel2 = new SongReadModel(
            StrTestHelper::generateUlid(),
            'I CAN\'T STOP ME',
            new DateTimeImmutable('2020-10-26'),
            'https://example2.youtube.com/watch?v=dQw4w9WgXcQ',
            '/resources/public/images/test2.webp',
        );
        $readModel1 = new GroupReadModel(
            StrTestHelper::generateUlid(),
            'TWICE',
            'JYP엔터테인먼트',
            '### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.',
            '/resources/public/images/test.webp',
            [$songReadModel1, $songReadModel2],
        );
        $songReadModel3 = new SongReadModel(
            StrTestHelper::generateUlid(),
            'Supernova',
            new DateTimeImmutable('2024-5-13'),
            'https://example3.youtube.com/watch?v=dQw4w9WgXcQ',
            '/resources/public/images/test3.webp',
        );
        $songReadModel4 = new SongReadModel(
            StrTestHelper::generateUlid(),
            'Armageddon',
            new DateTimeImmutable('2024-5-27'),
            'https://example4.youtube.com/watch?v=dQw4w9WgXcQ',
            '/resources/public/images/test4.webp',
        );
        $readModel2 = new GroupReadModel(
            StrTestHelper::generateUlid(),
            'aespa',
            'SM엔터테인먼트입니다',
            '에스파는 2020년 11월 17일에 데뷔한 한국의 4인조 걸그룹입니다. 멤버는 한국인인 **카리나**와 **윈터**, 일본인인 **지젤**, 그리고 중국인인 **닝닝**으로 구성되어 있습니다.
그룹명 \'aespa\'는 \'Avatar X Experience\'를 표현하는 \'æ\'와 양면이라는 뜻의 영단어 \'aspect\'를 결합하여 만든 이름입니다. \'자신의 또 다른 자아인 아바타를 만나, 새로운 세계를 경험한다\'는 독자적인 메타버스 세계관을 콘셉트로 활동하고 있습니다. 멤버 각각에게는 가상 세계에 존재하는 아바타 \'æ-aespa(아이-에스파)\'가 있으며, 현실 세계의 멤버와 아바타가 \'SYNK(싱크)\'라는 신호를 통해 연결되어 있다는 장대한 스토리가 곡과 뮤직비디오를 통해 그려지고 있습니다.
데뷔곡 **\'Black Mamba\'**로 강렬한 인상을 남겼으며, 그 후 **\'Next Level\'**, **\'Savage\'**, **\'Spicy\'**, **\'Supernova\'**, **\'Armageddon\'** 등 중독성 강한 사운드와 파워풀한 퍼포먼스로 연이어 히트곡을 탄생시키며 K팝 4세대를 대표하는 걸그룹으로 자리매김했습니다.',
            '/resources/public/images/test.webp',
            [$songReadModel3, $songReadModel4],
        );
        $groups = [$readModel1, $readModel2];
        $currentPage = 1;
        $lastPage = 2;
        $total = 3;
        $output = new GetGroupsOutput();
        $output->output(
            $groups,
            $currentPage,
            $lastPage,
            $total,
        );
        $this->assertSame([
            'groups' => [$readModel1->toArray(), $readModel2->toArray()],
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'total' => $total,
        ], $output->toArray());
    }
}
