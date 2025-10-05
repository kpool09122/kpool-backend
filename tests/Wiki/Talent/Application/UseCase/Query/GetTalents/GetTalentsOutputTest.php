<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Query\GetTalents;

use DateTimeImmutable;
use Source\Wiki\Talent\Application\UseCase\Query\GetTalents\GetTalentsOutput;
use Source\Wiki\Talent\Application\UseCase\Query\SongReadModel;
use Source\Wiki\Talent\Application\UseCase\Query\TalentReadModel;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetTalentsOutputTest extends TestCase
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
            'Mirage',
            new DateTimeImmutable('2024-11-6'),
            'https://example1.youtube.com/watch?v=dQw4w9WgXcQ',
            '/resources/public/images/test1.webp',
        );
        $readModel1 = new TalentReadModel(
            StrTestHelper::generateUlid(),
            '사나',
            '미나토자키 사나',
            ['TWICE', 'MISAMO'],
            new DateTimeImmutable('1996-12-29'),
            '오사카부 출신. 중학교 3학년 때 쇼핑몰에서 캐스팅되어 JYP엔터테인먼트의 연습생이 되었다. 약 3년 7개월의 트레이닝 기간을 거쳐, 2015년 서바이벌 오디션 프로그램 \'SIXTEEN\'에 참가하여 멋지게 트와이스 멤버로 데뷔했다. 그룹에서는 서브보컬을 담당하고 있다. 타고난 애교 넘치는 캐릭터와 \'큐티 섹시\'로 불리는 매력적인 퍼포먼스로 전 세계 팬들을 사로잡고 있다. 유창한 한국어 능력도 높이 평가받고 있으며, 최근에는 브랜드 앰배서더나 방송 MC 등 그룹 활동 외에도 활동 영역을 넓히고 있다.',
            '/resources/public/images/test.webp',
            [$songReadModel1],
        );
        $songReadModel2 = new SongReadModel(
            StrTestHelper::generateUlid(),
            'Killin\' Me Good',
            new DateTimeImmutable('2023-8-18'),
            'https://example2.youtube.com/watch?v=dQw4w9WgXcQ',
            '/resources/public/images/test2.webp',
        );
        $songReadModel3 = new SongReadModel(
            StrTestHelper::generateUlid(),
            'Stardust love song',
            new DateTimeImmutable('2022-3-6'),
            'https://example3.youtube.com/watch?v=dQw4w9WgXcQ',
            '/resources/public/images/test3.webp',
        );
        $readModel2 = new TalentReadModel(
            StrTestHelper::generateUlid(),
            '지효',
            '박지효',
            ['TWICE'],
            new DateTimeImmutable('1997-2-1'),
            '8살이라는 어린 나이에 JYP엔터테인먼트에 입사하여 약 10년간의 긴 연습생 생활을 보낸 노력가로 알려져 있다. 2015년 오디션 프로그램 \'SIXTEEN\'을 통해 멤버들의 투표로 트와이스의 리더로 선정되었으며, 메인보컬로서 그룹을 이끌고 있다. 그 파워풀하고 안정감 있는 압도적인 가창력은 트와이스 퍼포먼스의 핵심이 되고 있다. 오랜 연습생 경험으로 다져진 실력과 리더십으로 멤버들을 아우르며, 그룹에 없어서는 안 될 정신적 지주이기도 하다. 2023년에는 미니앨범 『ZONE』으로 대망의 솔로 데뷔를 완수했다.',
            '/resources/public/images/test.webp',
            [$songReadModel2, $songReadModel3],
        );
        $talents = [$readModel1, $readModel2];
        $currentPage = 1;
        $lastPage = 2;
        $total = 3;
        $output = new GetTalentsOutput();
        $output->output(
            $talents,
            $currentPage,
            $lastPage,
            $total,
        );
        $this->assertSame([
            'talents' => [$readModel1->toArray(), $readModel2->toArray()],
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'total' => $total,
        ], $output->toArray());
    }
}
