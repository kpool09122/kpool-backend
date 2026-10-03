<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Query\AuthenticationMethodsReadModel;

class AuthenticationMethodsReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new AuthenticationMethodsReadModel(7, ['google', 'line']);
        $this->assertSame([
            'passkeyCount' => 7,
            'linkedSocialProviders' => ['google', 'line'],
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new AuthenticationMethodsReadModel(7, ['google', 'line']);
        $this->assertSame([
            'passkeyCount' => 7,
            'linkedSocialProviders' => ['google', 'line'],
        ], $subject->toArray());
    }
}
