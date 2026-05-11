<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Identity\Support;

use Application\Http\Action\Identity\Support\IdentityResponsePayload;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class IdentityResponsePayloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost:8080',
            'filesystems.disks.public.url' => 'http://localhost:8080/storage',
        ]);
        URL::forceRootUrl('http://localhost:8080');
    }

    public function testNormalizeProfileImageConvertsRelativePathToPublicUrl(): void
    {
        $payload = IdentityResponsePayload::normalizeProfileImage([
            'profileImage' => 'images/profile.webp',
        ]);

        $this->assertSame('http://localhost:8080/storage/images/profile.webp', $payload['profileImage']);
    }

    public function testNormalizeProfileImageConvertsAbsolutePathToAppUrl(): void
    {
        $payload = IdentityResponsePayload::normalizeProfileImage([
            'profileImage' => '/storage/images/profile.webp',
        ]);

        $this->assertSame('http://localhost:8080/storage/images/profile.webp', $payload['profileImage']);
    }

    public function testNormalizeProfileImageKeepsNull(): void
    {
        $payload = IdentityResponsePayload::normalizeProfileImage([
            'profileImage' => null,
        ]);

        $this->assertNull($payload['profileImage']);
    }
}
