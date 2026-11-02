<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Application\UseCase\Query\PrincipalReadModel;

class PrincipalReadModelTest extends TestCase
{
    public function testSerializationPreservesStatusAndPolicies(): void
    {
        $policies = [['policyIdentifier' => 'policy', 'name' => 'General', 'statements' => [['effect' => 'allow', 'actions' => ['analysis:view'], 'resourceTypes' => ['analysis']]]]];
        $model = new PrincipalReadModel('principal', 'identity', 'active', $policies);
        self::assertSame(['principalIdentifier' => 'principal', 'identityIdentifier' => 'identity', 'status' => 'active', 'policies' => $policies], $model->toArray());
    }

    public function testEmptyPoliciesAndStoppedStateRemainVisible(): void
    {
        $model = new PrincipalReadModel('principal', 'identity', 'forced_stop', []);
        self::assertSame(['principalIdentifier' => 'principal', 'identityIdentifier' => 'identity', 'status' => 'forced_stop', 'policies' => []], $model->toArray());
    }
}
