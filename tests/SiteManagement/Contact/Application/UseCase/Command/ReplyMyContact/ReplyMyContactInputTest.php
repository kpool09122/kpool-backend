<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Command\ReplyMyContact;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyMyContact\ReplyMyContactInput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ReplyMyContactInputTest extends TestCase
{
    public function testPreservesContactAuthenticatedPrincipalAndContent(): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ReplyMyContactInput($contactIdentifier, $principalIdentifier, '追加の質問です。');

        $this->assertSame($contactIdentifier, $input->contactIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame('追加の質問です。', $input->content());
    }
}
