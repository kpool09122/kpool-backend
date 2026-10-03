<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;

class ContactReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new ContactReadModel('contactIdentifier-value', 'identityIdentifier-value', 7, 'name-value', ['reply-1', 'reply-2'], 'createdAt-value');
        $this->assertSame([
            'contactIdentifier' => 'contactIdentifier-value',
            'identityIdentifier' => 'identityIdentifier-value',
            'category' => 7,
            'name' => 'name-value',
            'replyIdentifiers' => ['reply-1', 'reply-2'],
            'createdAt' => 'createdAt-value',
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new ContactReadModel('contactIdentifier-value', null, 7, 'name-value', ['reply-1', 'reply-2'], 'createdAt-value');
        $this->assertSame([
            'contactIdentifier' => 'contactIdentifier-value',
            'identityIdentifier' => null,
            'category' => 7,
            'name' => 'name-value',
            'replyIdentifiers' => ['reply-1', 'reply-2'],
            'createdAt' => 'createdAt-value',
        ], $subject->toArray());
    }
}
