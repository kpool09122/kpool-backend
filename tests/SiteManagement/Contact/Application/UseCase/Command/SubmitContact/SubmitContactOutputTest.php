<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactOutput;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Tests\Helper\StrTestHelper;

class SubmitContactOutputTest extends TestCase
{
    public function testToArrayWithContact(): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            $contactIdentifier,
            $identityIdentifier,
            Category::SUGGESTIONS,
            new ContactName('問い合わせ太郎'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容'),
        );

        $output = new SubmitContactOutput();
        $output->setContact($contact);

        $this->assertSame($contact, $output->contact());
        $this->assertSame([
            'contactIdentifier' => (string) $contactIdentifier,
            'identityIdentifier' => (string) $identityIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ太郎',
            'email' => 'john.doe@example.com',
            'content' => 'お問い合わせ内容',
        ], $output->toArray());
    }

    public function testToArrayWithoutContact(): void
    {
        $output = new SubmitContactOutput();

        $this->assertNull($output->contact());
        $this->assertSame([
            'contactIdentifier' => null,
            'identityIdentifier' => null,
            'category' => null,
            'name' => null,
            'email' => null,
            'content' => null,
        ], $output->toArray());
    }
}
