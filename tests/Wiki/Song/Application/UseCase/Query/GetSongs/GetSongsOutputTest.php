<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Query\GetSongs;

use DateTimeImmutable;
use Source\Wiki\Song\Application\UseCase\Query\GetSongs\GetSongsOutput;
use Source\Wiki\Song\Application\UseCase\Query\SongReadModel;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetSongsOutputTest extends TestCase
{
    /**
     * 正常系: Outputへも追加とtoArrayによる出力がうまく動作すること.
     *
     * @return void
     */
    public function testOutput(): void
    {
        $readModel1 = new SongReadModel(
            StrTestHelper::generateUuid(),
            'TT',
            ['TWICE'],
            'Sam Lewis',
            '블랙아이드필승',
            new DateTimeImmutable('2016-10-24'),
            '2016년에 발매된 세 번째 미니앨범 『TWICEcoaster : LANE 1』의 타이틀곡입니다. 좋아하는 사람 앞에서 마음처럼 되지 않는 안타까운 사랑의 마음을 노래하고 있습니다. 곡명 \'TT\'는 양손의 엄지와 검지로 화살표를 만들어 우는 이모티콘(T_T)을 표현한 것으로, 이 \'TT 포즈\'는 곡과 함께 사회적 현상을 일으킬 정도의 큰 붐을 일으켰습니다. 중독성 강한 멜로디와 누구나 따라 하기 쉬운 캐치한 안무가 특징으로, 트와이스의 인기를 확고히 한 대표곡입니다. 핼러윈을 테마로 한 뮤직비디오에서는 멤버들이 동화 속 캐릭터 등으로 분장한 귀여운 모습도 화제를 모았습니다.',
            'https://example2.youtube.com/watch?v=dQw4w9WgXcQ',
            '/resources/public/images/test2.webp',
        );
        $readModel2 = new SongReadModel(
            StrTestHelper::generateUuid(),
            'I CAN\'T STOP ME',
            ['TWICE'],
            'J.Y. Park',
            'Melanie Joy Fontana',
            new DateTimeImmutable('2020-10-26'),
            '2020년에 발매된 두 번째 정규 앨범 『Eyes wide open』의 타이틀곡입니다. 선과 악의 갈림길에서 갈등하면서도 자기 자신을 제어할 수 없게 되는 위험한 심정을 그리고 있습니다. 80년대가 연상되는 레트로한 분위기의 신시사이저 사운드가 인상적인 \'신스웨이브\' 장르의 곡입니다. 기존의 밝고 활기찬 이미지에서 한 걸음 더 나아가, 한층 더 성숙하고 대담한 콘셉트와 파워풀한 퍼포먼스로 트와이스의 새로운 매력을 전 세계에 보여주었습니다. 뮤직비디오에서는 화려한 비주얼과 함께, 멤버들끼리 마주 보는 장면 등을 통해 내면의 갈등을 표현하고 있습니다.',
            'https://example2.youtube.com/watch?v=dQw4w9WgXcQ',
            '/resources/public/images/test2.webp',
        );
        $songs = [$readModel1, $readModel2];
        $currentPage = 1;
        $lastPage = 2;
        $total = 3;
        $output = new GetSongsOutput();
        $output->output(
            $songs,
            $currentPage,
            $lastPage,
            $total,
        );
        $this->assertSame([
            'songs' => [$readModel1->toArray(), $readModel2->toArray()],
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'total' => $total,
        ], $output->toArray());
    }
}
