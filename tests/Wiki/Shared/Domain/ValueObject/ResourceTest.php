<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    /**
     * 正常系：リソースIDインスタンスが正しく作成されること,
     *
     * @return void
     */
    public function test__construct(): void
    {
        $resourceType = ResourceType::AGENCY;
        $agencyId = StrTestHelper::generateUuid();
        $groupIds = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        $talentIds = [StrTestHelper::generateUuid()];
        $isOfficial = true;
        $resource = new Resource(
            $resourceType,
            $agencyId,
            $groupIds,
            $talentIds,
            $isOfficial,
        );
        $this->assertSame($resourceType->value, $resource->type()->value);
        $this->assertSame($agencyId, $resource->agencyId());
        $this->assertSame($groupIds, $resource->groupIds());
        $this->assertSame($talentIds, $resource->talentIds());
        $this->assertSame($isOfficial, $resource->isOfficial());
    }

    /**
     * 異常系：事務所IDの値が不正の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenAgencyIdIsInvalid(): void
    {
        $resourceType = ResourceType::AGENCY;
        $agencyId = 'test-invalid-agency-id';
        $groupIds = [StrTestHelper::generateUuid()];
        $talentIds = [StrTestHelper::generateUuid()];
        $this->expectException(InvalidArgumentException::class);
        new Resource(
            $resourceType,
            $agencyId,
            $groupIds,
            $talentIds,
        );
    }

    /**
     * 異常系：グループID配列の値が不正の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenGroupIdsContainInvalid(): void
    {
        $resourceType = ResourceType::AGENCY;
        $agencyId = StrTestHelper::generateUuid();
        $groupIds = [StrTestHelper::generateUuid(), 'invalid-group-id'];
        $talentIds = [StrTestHelper::generateUuid()];
        $this->expectException(InvalidArgumentException::class);
        new Resource(
            $resourceType,
            $agencyId,
            $groupIds,
            $talentIds,
        );
    }

    /**
     * 異常系：メンバーIDの値が不正の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenTalentIdIsInvalid(): void
    {
        $resourceType = ResourceType::AGENCY;
        $agencyId = StrTestHelper::generateUuid();
        $groupIds = [StrTestHelper::generateUuid()];
        $talentIds = ['test-invalid-talent-id'];
        $this->expectException(InvalidArgumentException::class);
        new Resource(
            $resourceType,
            $agencyId,
            $groupIds,
            $talentIds,
        );
    }
}
