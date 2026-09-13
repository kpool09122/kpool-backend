<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Http\Action\Command;

use Application\Http\Action\Account\PrincipalGroup\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersRequest;
use Illuminate\Support\Facades\Validator;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdatePrincipalGroupMembersRequestTest extends TestCase
{
    public function testAllowsSamePrincipalIdentifierInDifferentPrincipalGroups(): void
    {
        $principalIdentifier = StrTestHelper::generateUuid();
        $request = UpdatePrincipalGroupMembersRequest::create('/', 'POST', [
            'principalGroups' => [
                [
                    'principalGroupIdentifier' => StrTestHelper::generateUuid(),
                    'principalIdentifiers' => [$principalIdentifier],
                ],
                [
                    'principalGroupIdentifier' => StrTestHelper::generateUuid(),
                    'principalIdentifiers' => [$principalIdentifier],
                ],
            ],
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails(), (string) $validator->errors());
    }

    public function testRejectsDuplicatePrincipalIdentifierInSamePrincipalGroup(): void
    {
        $principalIdentifier = StrTestHelper::generateUuid();
        $request = UpdatePrincipalGroupMembersRequest::create('/', 'POST', [
            'principalGroups' => [
                [
                    'principalGroupIdentifier' => StrTestHelper::generateUuid(),
                    'principalIdentifiers' => [$principalIdentifier, $principalIdentifier],
                ],
            ],
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('principalGroups.0.principalIdentifiers.1', $validator->errors()->toArray());
    }
}
