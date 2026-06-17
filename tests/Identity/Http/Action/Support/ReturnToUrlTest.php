<?php

declare(strict_types=1);

namespace Tests\Identity\Http\Action\Support;

use Application\Http\Action\Identity\Support\ReturnToUrl;
use Tests\TestCase;

class ReturnToUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.frontend_url', 'http://localhost:3000');
    }

    public function testNormalizeAcceptsRelativePath(): void
    {
        $this->assertSame('/mypage/wiki?tab=draft#top', ReturnToUrl::normalize('/mypage/wiki?tab=draft#top'));
    }

    public function testNormalizeAcceptsSameOriginUrlAsRelativePath(): void
    {
        $this->assertSame('/mypage/wiki', ReturnToUrl::normalize('http://localhost:3000/mypage/wiki'));
    }

    public function testNormalizeRejectsExternalUrl(): void
    {
        $this->assertNull(ReturnToUrl::normalize('https://example.com/phishing'));
    }

    public function testToFrontendUrlFallsBackToDefaultPath(): void
    {
        $this->assertSame('http://localhost:3000/auth/callback', ReturnToUrl::toFrontendUrl('https://example.com/phishing'));
    }
}
