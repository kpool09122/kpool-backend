<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\UseCase\Query\GetAgency;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Businesses\Wiki\Agency\UseCase\Query\GetAgency\GetAgencyInput;
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
        $translation = Translation::KOREAN;
        $input = new GetAgencyInput($agencyIdentifier, $translation);
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame($translation->value, $input->translation()->value);
    }
}
