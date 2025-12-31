<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Query;

use DateTimeImmutable;
use Source\Wiki\Group\Application\UseCase\Query\GroupReadModel;
use Source\Wiki\Group\Application\UseCase\Query\SongReadModel;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GroupReadModelTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $name = 'TWICE';
        $agencyName = 'JYP엔터테인먼트';
        $description = '### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.';
        $imageUrl = 'https://example.com/resources/public/images/image.webp';
        $songReadModel1 = new SongReadModel(
            StrTestHelper::generateUuid(),
            'TT',
            new DateTimeImmutable('2016-10-24'),
            'https://example.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://example.com/resources/public/images/image.webp',
        );
        $songReadModel2 = new SongReadModel(
            StrTestHelper::generateUuid(),
            'I CAN\'T STOP ME',
            new DateTimeImmutable('2020-10-26'),
            'https://example2.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://example.com/resources/public/images/image2.webp',
        );
        $songReadModels = [$songReadModel1, $songReadModel2];
        $readModel = new GroupReadModel(
            $groupId,
            $name,
            $agencyName,
            $description,
            $imageUrl,
            $songReadModels,
        );
        $this->assertSame($groupId, $readModel->groupId());
        $this->assertSame($name, $readModel->name());
        $this->assertSame($agencyName, $readModel->agencyName());
        $this->assertSame($description, $readModel->description());
        $this->assertSame($imageUrl, $readModel->imageUrl());
        $this->assertSame($songReadModels, $readModel->songReadModels());
        $this->assertSame([
            'group_id' => $groupId,
            'name' => $name,
            'company_name' => $agencyName,
            'description' => $description,
            'image_url' => $imageUrl,
            'songs' => [$songReadModel1->toArray(), $songReadModel2->toArray()],
        ], $readModel->toArray());
    }
}
