<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

class ResourceTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $type = ResourceType::ACCOUNT;
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $accountType = AccountType::CORPORATION;
        $accountCategory = AccountCategory::AGENCY;
        $affiliationRequestingAccountCategory = AccountCategory::AGENCY;
        $delegationIdentifier = new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $targetAccountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new Resource($type, $accountIdentifier, $accountType, $accountCategory, $affiliationRequestingAccountCategory, $delegationIdentifier, $targetAccountIdentifier);

        $this->assertSame($type, $subject->type());
        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
        $this->assertSame($accountType, $subject->accountType());
        $this->assertSame($accountCategory, $subject->accountCategory());
        $this->assertSame($affiliationRequestingAccountCategory, $subject->affiliationRequestingAccountCategory());
        $this->assertSame($delegationIdentifier, $subject->delegationIdentifier());
        $this->assertSame($targetAccountIdentifier, $subject->targetAccountIdentifier());
    }

    public function testAllowsAbsentOptionalValues(): void
    {
        $subject = new Resource(ResourceType::ACCOUNT, new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), null, null, null, null, null);
        $this->assertNull($subject->accountType());
        $this->assertNull($subject->accountCategory());
        $this->assertNull($subject->affiliationRequestingAccountCategory());
        $this->assertNull($subject->delegationIdentifier());
        $this->assertNull($subject->targetAccountIdentifier());
    }

    public function testAccountResourceFactory(): void
    {
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $resource = Resource::account($accountIdentifier, AccountType::INDIVIDUAL, AccountCategory::TALENT, AccountCategory::AGENCY);
        $this->assertSame(ResourceType::ACCOUNT, $resource->type());
        $this->assertSame($accountIdentifier, $resource->accountIdentifier());
        $this->assertSame(AccountType::INDIVIDUAL, $resource->accountType());
        $this->assertSame(AccountCategory::TALENT, $resource->accountCategory());
        $this->assertSame(AccountCategory::AGENCY, $resource->affiliationRequestingAccountCategory());
        $this->assertNull($resource->delegationIdentifier());
        $this->assertNull($resource->targetAccountIdentifier());
    }

    public function testDelegationResourceFactory(): void
    {
        $source = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $target = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000002');
        $delegationIdentifier = new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000003');
        $resource = Resource::delegationAccount($source, $delegationIdentifier, $target);
        $this->assertSame(ResourceType::ACCOUNT, $resource->type());
        $this->assertSame($source, $resource->accountIdentifier());
        $this->assertSame($target, $resource->targetAccountIdentifier());
        $this->assertSame($delegationIdentifier, $resource->delegationIdentifier());
        $this->assertNull($resource->accountType());
        $this->assertNull($resource->accountCategory());
    }
}
