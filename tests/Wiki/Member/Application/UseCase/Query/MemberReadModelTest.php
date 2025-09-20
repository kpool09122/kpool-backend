<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Query;

use DateTimeImmutable;
use Source\Wiki\Member\Application\UseCase\Query\MemberReadModel;
use Source\Wiki\Member\Application\UseCase\Query\SongReadModel;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MemberReadModelTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $memberId = StrTestHelper::generateUlid();
        $name = '채영';
        $realName = '손채영';
        $groupNames = ['TWICE', 'MISAMO'];
        $birthday = new DateTimeImmutable('1994-01-01');
        $career = '### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.';
        $imageUrl = 'https://example.com/resources/public/images/image.webp';
        $songReadModel1 = new SongReadModel(
            StrTestHelper::generateUlid(),
            'TT',
            new DateTimeImmutable('2016-10-24'),
            'https://example.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://example.com/resources/public/images/image.webp',
        );
        $songReadModel2 = new SongReadModel(
            StrTestHelper::generateUlid(),
            'I CAN\'T STOP ME',
            new DateTimeImmutable('2020-10-26'),
            'https://example2.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://example.com/resources/public/images/image2.webp',
        );
        $songReadModels = [$songReadModel1, $songReadModel2];
        $readModel = new MemberReadModel(
            $memberId,
            $name,
            $realName,
            $groupNames,
            $birthday,
            $career,
            $imageUrl,
            $songReadModels,
        );
        $this->assertSame($memberId, $readModel->memberId());
        $this->assertSame($name, $readModel->name());
        $this->assertSame($realName, $readModel->realName());
        $this->assertSame($groupNames, $readModel->groupNames());
        $this->assertSame($birthday, $readModel->birthday());
        $this->assertSame($career, $readModel->career());
        $this->assertSame($imageUrl, $readModel->imageUrl());
        $this->assertSame($songReadModels, $readModel->songReadModels());
        $this->assertSame([
            'member_id' => $memberId,
            'name' => $name,
            'real_name' => $realName,
            'group_name' => $groupNames,
            'birthday' => $birthday,
            'career' => $career,
            'image_url' => $imageUrl,
            'songs' => [$songReadModel1->toArray(), $songReadModel2->toArray()],
        ], $readModel->toArray());
    }
}
