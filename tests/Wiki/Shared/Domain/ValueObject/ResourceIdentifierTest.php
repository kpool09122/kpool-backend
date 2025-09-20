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
        $id = StrTestHelper::generateUlid();
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [StrTestHelper::generateUlid(), StrTestHelper::generateUlid()];
        $memberId = StrTestHelper::generateUlid();
        $resourceIdentifier = new ResourceIdentifier(
            $resourceType,
            $id,
            $agencyId,
            $groupIds,
            $memberId,
        );
        $this->assertSame($resourceType->value, $resourceIdentifier->type()->value);
        $this->assertSame($id, $resourceIdentifier->id());
        $this->assertSame($agencyId, $resourceIdentifier->agencyId());
        $this->assertSame($groupIds, $resourceIdentifier->groupIds());
        $this->assertSame($memberId, $resourceIdentifier->memberId());
    }

    /**
     * 異常系：リソースIDの値が不正の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenIdIsInvalid(): void
    {
        $resourceType = ResourceType::AGENCY;
        $id = 'test-invalid-id';
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [StrTestHelper::generateUlid()];
        $memberId = StrTestHelper::generateUlid();
        $this->expectException(InvalidArgumentException::class);
        new ResourceIdentifier(
            $resourceType,
            $id,
            $agencyId,
            $groupIds,
            $memberId,
        );
    }

    /**
     * 異常系：事務所IDの値が不正の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenAgencyIdIsInvalid(): void
    {
        $resourceType = ResourceType::AGENCY;
        $id = StrTestHelper::generateUlid();
        $agencyId = 'test-invalid-agency-id';
        $groupIds = [StrTestHelper::generateUlid()];
        $memberId = StrTestHelper::generateUlid();
        $this->expectException(InvalidArgumentException::class);
        new ResourceIdentifier(
            $resourceType,
            $id,
            $agencyId,
            $groupIds,
            $memberId,
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
        $id = StrTestHelper::generateUlid();
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [StrTestHelper::generateUlid(), 'invalid-group-id'];
        $memberId = StrTestHelper::generateUlid();
        $this->expectException(InvalidArgumentException::class);
        new ResourceIdentifier(
            $resourceType,
            $id,
            $agencyId,
            $groupIds,
            $memberId,
        );
    }

    /**
     * 異常系：メンバーIDの値が不正の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenMemberIdIsInvalid(): void
    {
        $resourceType = ResourceType::AGENCY;
        $id = StrTestHelper::generateUlid();
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [StrTestHelper::generateUlid()];
        $memberId = 'test-invalid-member-id';
        $this->expectException(InvalidArgumentException::class);
        new ResourceIdentifier(
            $resourceType,
            $id,
            $agencyId,
            $groupIds,
            $memberId,
        );
    }
}
