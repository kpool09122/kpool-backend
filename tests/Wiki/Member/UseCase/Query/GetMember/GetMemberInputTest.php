<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\UseCase\Query\GetMember;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Wiki\Member\UseCase\Query\GetMember\GetMemberInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetMemberInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $input = new GetMemberInput($memberIdentifier, $translation);
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
        $this->assertSame($translation->value, $input->translation()->value);
    }
}
