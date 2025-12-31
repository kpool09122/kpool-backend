<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ResourceIdentifierTest extends TestCase
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
        $resourceIdentifier = new ResourceIdentifier(
            $resourceType,
            $agencyId,
            $groupIds,
            $talentIds,
        );
        $this->assertSame($resourceType->value, $resourceIdentifier->type()->value);
        $this->assertSame($agencyId, $resourceIdentifier->agencyId());
        $this->assertSame($groupIds, $resourceIdentifier->groupIds());
        $this->assertSame($talentIds, $resourceIdentifier->talentIds());
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
        new ResourceIdentifier(
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
        new ResourceIdentifier(
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
        new ResourceIdentifier(
            $resourceType,
            $agencyId,
            $groupIds,
            $talentIds,
        );
    }
}
