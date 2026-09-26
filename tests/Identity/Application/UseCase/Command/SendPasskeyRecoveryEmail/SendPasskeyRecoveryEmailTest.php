<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryEmailVerificationServiceInterface;
use Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail\SendPasskeyRecoveryEmail;
use Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail\SendPasskeyRecoveryEmailInput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SendPasskeyRecoveryEmailTest extends TestCase
{
    #[DataProvider('identityProvider')]
    public function testItKeepsTheSameExternalBehaviorWhetherTheIdentityExists(bool $exists): void
    {
        $email = new Email('user@example.com');
        $identityId = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $identity = $exists ? new Identity($identityId, new IdentityName('user'), $email, Language::JAPANESE, null, new DateTimeImmutable()) : null;
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturn($identity);
        /** @var MockInterface&PasskeyRecoveryEmailVerificationServiceInterface $verification */
        $verification = Mockery::mock(PasskeyRecoveryEmailVerificationServiceInterface::class);
        $verification->shouldReceive('send')->once()->with(
            $email,
            Mockery::on(static fn (?IdentityIdentifier $actual): bool => $exists
                ? (string) $actual === (string) $identityId
                : $actual === null),
            Language::JAPANESE,
        );

        (new SendPasskeyRecoveryEmail($identityRepository, $verification))->process(
            new SendPasskeyRecoveryEmailInput($email, Language::JAPANESE),
        );

        $this->addToAssertionCount(1);
    }

    /** @return array<array{bool}> */
    public static function identityProvider(): array
    {
        return [[true], [false]];
    }
}
