<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Query\GetTalent;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Talent\Application\UseCase\Query\GetTalent\GetTalentInput;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetTalentInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $input = new GetTalentInput($talentIdentifier, $language);
        $this->assertSame((string)$talentIdentifier, (string)$input->talentIdentifier());
        $this->assertSame($language->value, $input->language()->value);
    }
}
