<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Identity\Application\Service\OAuthStateGeneratorInterface;
use Source\Identity\Infrastructure\Service\OAuthStateGenerator;
use Tests\TestCase;

class OAuthStateGeneratorTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $generator = $this->app()->make(OAuthStateGeneratorInterface::class);

        $this->assertInstanceOf(OAuthStateGenerator::class, $generator);
    }

    /**
     * 正常系: generateメソッドでOAuthStateが生成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGenerate(): void
    {
        $generator = $this->app()->make(OAuthStateGeneratorInterface::class);
        $before = new DateTimeImmutable('+9 minutes');
        $after = new DateTimeImmutable('+11 minutes');

        $state = $generator->generate();

        $this->assertSame(64, mb_strlen((string) $state->state));
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{64}\z/', (string) $state->state);
        $this->assertGreaterThan($before, $state->expiresAt);
        $this->assertLessThan($after, $state->expiresAt);
    }

    /**
     * 正常系: 複数回呼び出すと異なるstateが生成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGenerateReturnsDifferentStates(): void
    {
        $generator = $this->app()->make(OAuthStateGeneratorInterface::class);

        $states = [];
        for ($i = 0; $i < 10; $i++) {
            $states[] = (string) $generator->generate()->state;
        }

        $uniqueStates = array_unique($states);
        $this->assertCount(10, $uniqueStates);
    }
}
