<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\MergeGroup;

use DateTimeImmutable;
use Source\Wiki\Group\Application\UseCase\Command\MergeGroup\MergeGroupInput;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MergeGroupInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다.');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $input = new MergeGroupInput(
            $groupIdentifier,
            $name,
            $agencyIdentifier,
            $description,
            $principalIdentifier,
            $mergedAt,
        );
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame((string)$name, (string)$input->name());
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame((string)$description, (string)$input->description());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($mergedAt, $input->mergedAt());
    }
}
