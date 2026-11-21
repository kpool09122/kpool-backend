<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Application\UseCase\Query\PrincipalReadModel;

class PrincipalReadModelTest extends TestCase
{
    public function testSerializationPreservesStatusAndPolicies(): void
    {
        $policies = [['policyIdentifier' => 'policy', 'name' => 'General', 'statements' => [['effect' => 'allow', 'actions' => ['principal-group:read'], 'resourceTypes' => ['principal_group'], 'condition' => null]]]];
        $model = new PrincipalReadModel('principal', 'identity', 'active', $policies, 2);
        self::assertSame(['principalIdentifier' => 'principal', 'identityIdentifier' => 'identity', 'status' => 'active', 'accountPrincipalCount' => 2, 'policies' => $policies], $model->toArray());
    }

    public function testEmptyPoliciesAndStoppedStateRemainVisible(): void
    {
        $model = new PrincipalReadModel('principal', 'identity', 'forced_stop', [], 1);
        self::assertSame(['principalIdentifier' => 'principal', 'identityIdentifier' => 'identity', 'status' => 'forced_stop', 'accountPrincipalCount' => 1, 'policies' => []], $model->toArray());
    }
}
