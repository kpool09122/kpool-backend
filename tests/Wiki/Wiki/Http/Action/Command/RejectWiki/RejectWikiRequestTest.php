<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Http\Action\Command\RejectWiki;

use Application\Http\Action\Wiki\Wiki\Command\RejectWiki\RejectWikiRequest;
use Illuminate\Support\Facades\Validator;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectWikiRequestTest extends TestCase
{
    public function testRulesRequireReason(): void
    {
        $request = new RejectWikiRequest();

        $this->assertSame(['required', 'string', 'filled', 'not_regex:/^\\s*$/u', 'max:1000'], $request->rules()['reason']);
    }

    public function testReasonReturnsInputValue(): void
    {
        $request = new RejectWikiRequest();
        $request->merge(['reason' => '内容が不十分です']);

        $this->assertSame('内容が不十分です', $request->reason());
    }

    public function testReasonValidationRejectsMissingEmptyAndWhitespaceOnlyValues(): void
    {
        $request = new RejectWikiRequest();
        $rules = $request->rules();
        $basePayload = [
            'wikiId' => StrTestHelper::generateUuid(),
            'resourceType' => 'group',
        ];

        foreach ([[], ['reason' => ''], ['reason' => '   ']] as $reasonPayload) {
            $validator = Validator::make([...$basePayload, ...$reasonPayload], $rules);

            $this->assertTrue($validator->fails());
            $this->assertArrayHasKey('reason', $validator->errors()->toArray());
        }
    }
}
