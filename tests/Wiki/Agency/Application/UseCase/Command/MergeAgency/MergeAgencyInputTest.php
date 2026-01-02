<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\MergeAgency;

use DateTimeImmutable;
use Source\Wiki\Agency\Application\UseCase\Command\MergeAgency\MergeAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MergeAgencyInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다.');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $input = new MergeAgencyInput(
            $agencyIdentifier,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $principalIdentifier,
            $mergedAt,
        );
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame((string)$name, (string)$input->name());
        $this->assertSame((string)$CEO, (string)$input->CEO());
        $this->assertSame($foundedIn->value(), $input->foundedIn()->value());
        $this->assertSame((string)$description, (string)$input->description());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($mergedAt, $input->mergedAt());
    }

    /**
     * 正常系: foundedInがnullの場合もインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullFoundedIn(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = null;
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $input = new MergeAgencyInput(
            $agencyIdentifier,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $principalIdentifier,
            $mergedAt,
        );
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame((string)$name, (string)$input->name());
        $this->assertSame((string)$CEO, (string)$input->CEO());
        $this->assertNull($input->foundedIn());
        $this->assertSame((string)$description, (string)$input->description());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($mergedAt, $input->mergedAt());
    }
}
