<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryEmailVerificationServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail\VerifyPasskeyRecoveryEmail;
use Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail\VerifyPasskeyRecoveryEmailInput;
use Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail\VerifyPasskeyRecoveryEmailOutput;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class VerifyPasskeyRecoveryEmailTest extends TestCase
{
    public function testItIssuesARecoverySessionAfterSuccessfulVerification(): void
    {
        $email = new Email('user@example.com');
        $code = new AuthCode('123456');
        $identityId = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $key = new PasskeyRecoveryKey('123e4567-e89b-72d3-a456-426614174001');
        /** @var MockInterface&PasskeyRecoveryEmailVerificationServiceInterface $verification */
        $verification = Mockery::mock(PasskeyRecoveryEmailVerificationServiceInterface::class);
        $verification->shouldReceive('verify')->once()->with($email, $code)->andReturn($identityId);
        /** @var MockInterface&PasskeyRecoverySessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(PasskeyRecoverySessionStorageServiceInterface::class);
        $sessions->shouldReceive('issue')->once()->with($identityId, 'email')->andReturn($key);
        $output = new VerifyPasskeyRecoveryEmailOutput();

        (new VerifyPasskeyRecoveryEmail($verification, $sessions))->process(
            new VerifyPasskeyRecoveryEmailInput($email, $code),
            $output,
        );

        $this->assertSame(['recoveryKey' => (string) $key], $output->toArray());
    }

    #[DataProvider('verificationFailureProvider')]
    public function testItDoesNotIssueASessionWhenVerificationFails(string $message): void
    {
        /** @var MockInterface&PasskeyRecoveryEmailVerificationServiceInterface $verification */
        $verification = Mockery::mock(PasskeyRecoveryEmailVerificationServiceInterface::class);
        $verification->shouldReceive('verify')->once()->andThrow(new PasskeyRecoveryVerificationFailedException($message));
        /** @var MockInterface&PasskeyRecoverySessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(PasskeyRecoverySessionStorageServiceInterface::class);
        $sessions->shouldNotReceive('issue');

        $this->expectException(PasskeyRecoveryVerificationFailedException::class);
        (new VerifyPasskeyRecoveryEmail($verification, $sessions))->process(
            new VerifyPasskeyRecoveryEmailInput(new Email('user@example.com'), new AuthCode('654321')),
            new VerifyPasskeyRecoveryEmailOutput(),
        );
    }

    /** @return array<string, array{string}> */
    public static function verificationFailureProvider(): array
    {
        return [
            'mismatch' => ['Recovery code is invalid or expired.'],
            'reuse' => ['Recovery code has already been used.'],
            'attempt limit' => ['Recovery verification attempt limit exceeded.'],
        ];
    }
}
