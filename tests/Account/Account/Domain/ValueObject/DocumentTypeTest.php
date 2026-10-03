<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\DocumentType;

class DocumentTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'RESIDENT_REGISTRATION' => 'resident_registration',
            'PASSPORT' => 'passport',
            'DRIVER_LICENSE' => 'driver_license',
            'SELFIE' => 'selfie',
            'BUSINESS_REGISTRATION' => 'business_registration',
            'CORPORATE_REGISTRY' => 'corporate_registry',
            'INCORPORATION_DOCUMENT' => 'incorporation_document',
            'REPRESENTATIVE_ID' => 'representative_id',
        ], array_column(DocumentType::cases(), 'value', 'name'));
    }
}
