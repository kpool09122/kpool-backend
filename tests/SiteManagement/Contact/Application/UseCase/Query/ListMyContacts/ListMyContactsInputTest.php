<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInput;
use Tests\Helper\StrTestHelper;

class ListMyContactsInputTest extends TestCase
{
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ListMyContactsInput($identityIdentifier);

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
    }
}
