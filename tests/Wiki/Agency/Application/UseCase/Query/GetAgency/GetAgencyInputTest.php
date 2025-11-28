<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Query\GetAgency;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgency\GetAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetAgencyInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translation = Language::KOREAN;
        $input = new GetAgencyInput($agencyIdentifier, $translation);
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame($translation->value, $input->language()->value);
    }
}
