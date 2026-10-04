<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Identity\Registration;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountEmailConflictException;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyInputPort;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyInterface;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyOutputPort;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInputPort;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackOutputPort;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AccountEmailConflictHttpTest extends TestCase
{
    private const string EMAIL = 'conflicted-account@example.com';

    protected function defineRoutes($router): void
    {
        $router->group([], dirname(__DIR__, 6) . '/routes/identity_api.php');
    }

    public function testPasskeyRegistrationRollsBackAndReturnsGeneric500(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $transactionLevel = DB::transactionLevel();
        $useCase = Mockery::mock(RegisterWithPasskeyInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (
                RegisterWithPasskeyInputPort $input,
                RegisterWithPasskeyOutputPort $output,
            ) use ($identityIdentifier, $transactionLevel): void {
                $this->assertSame($transactionLevel + 1, DB::transactionLevel());
                CreateIdentity::create($identityIdentifier, ['email' => self::EMAIL]);

                throw new AccountEmailConflictException();
            });
        $this->app()->instance(RegisterWithPasskeyInterface::class, $useCase);

        $response = $this->withHeader('Accept-Language', 'ja-JP')->postJson('/auth/passkeys/registration', [
            'challengeKey' => StrTestHelper::generateUuid(),
            'identityName' => '競合テスト',
            'displayName' => 'Passkey',
            'credential' => [
                'id' => 'credential-id',
                'rawId' => 'credential-id',
                'type' => 'public-key',
                'response' => [
                    'clientDataJSON' => 'client-data',
                    'attestationObject' => 'attestation',
                ],
            ],
        ]);

        $this->assertGenericConflictResponse($response->getContent());
        $response->assertStatus(500)->assertJsonPath('message', 'サーバーエラーが発生しました。');
        $this->assertSame($transactionLevel, DB::transactionLevel());
        $this->assertDatabaseMissing('identities', ['id' => (string) $identityIdentifier]);
        $this->assertGuest();
    }

    public function testSocialRegistrationRollsBackAndReturnsGeneric500(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $transactionLevel = DB::transactionLevel();
        $useCase = Mockery::mock(SocialLoginCallbackInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (
                SocialLoginCallbackInputPort $input,
                SocialLoginCallbackOutputPort $output,
            ) use ($identityIdentifier, $transactionLevel): void {
                $this->assertSame($transactionLevel + 1, DB::transactionLevel());
                CreateIdentity::create($identityIdentifier, ['email' => self::EMAIL]);

                throw new AccountEmailConflictException();
            });
        $this->app()->instance(SocialLoginCallbackInterface::class, $useCase);

        $response = $this->withHeader('Accept-Language', 'ja-JP')
            ->getJson('/auth/social/google/callback?code=oauth-code&state=oauth-state');

        $this->assertGenericConflictResponse($response->getContent());
        $response->assertStatus(500)->assertJsonPath('message', 'サーバーエラーが発生しました。');
        $this->assertSame($transactionLevel, DB::transactionLevel());
        $this->assertDatabaseMissing('identities', ['id' => (string) $identityIdentifier]);
        $this->assertGuest();
    }

    private function assertGenericConflictResponse(string|false $content): void
    {
        $this->assertIsString($content);
        $this->assertStringNotContainsString(self::EMAIL, $content);
        $this->assertStringNotContainsString('An account already exists', $content);
    }
}
