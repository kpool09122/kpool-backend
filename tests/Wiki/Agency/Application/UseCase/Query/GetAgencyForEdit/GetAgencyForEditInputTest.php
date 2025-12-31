<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Query\GetAgencyForEdit;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencyForEdit\GetAgencyForEditInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetAgencyForEditInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translation = Language::KOREAN;
        $input = new GetAgencyForEditInput($agencyIdentifier, $translation);
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame($translation->value, $input->language()->value);
    }
}
