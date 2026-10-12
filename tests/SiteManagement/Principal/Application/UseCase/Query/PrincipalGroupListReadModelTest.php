<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupListReadModel;
use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupReadModel;

class PrincipalGroupListReadModelTest extends TestCase
{
    public function testCompleteListIncludesUnassignedPrincipals(): void
    {
        $member = new PrincipalGroupMemberReadModel('principal', 'identity', 'Alice', 'alice@example.com');
        $group = new PrincipalGroupReadModel('group', 'account', 'General', ['role'], true, []);
        $model = new PrincipalGroupListReadModel([$group], [$member]);
        self::assertSame([$group], $model->principalGroups());
        self::assertSame([$member], $model->principals());
        self::assertSame(['principalGroups' => [$group->toArray()],'principals' => [$member->toArray()]], $model->toArray());
        self::assertSame(['principalGroups' => [], 'principals' => []], (new PrincipalGroupListReadModel([], []))->toArray());
    }
}
