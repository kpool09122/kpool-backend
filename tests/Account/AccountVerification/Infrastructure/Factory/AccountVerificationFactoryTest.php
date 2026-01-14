<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\AccountVerification\Domain\Factory\AccountVerificationFactoryInterface;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Account\AccountVerification\Infrastructure\Factory\AccountVerificationFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountVerificationFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(AccountVerificationFactoryInterface::class);
        $this->assertInstanceOf(AccountVerificationFactory::class, $factory);
    }

    /**
     * 正常系: 正しくAccountVerificationエンティティが作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::TALENT;
        $applicantInfo = new ApplicantInfo('Taro Yamada');

        $factory = $this->app->make(AccountVerificationFactoryInterface::class);
        $verification = $factory->create(
            $accountIdentifier,
            $verificationType,
            $applicantInfo,
        );

        $this->assertTrue(UuidValidator::isValid((string) $verification->verificationIdentifier()));
        $this->assertSame((string) $accountIdentifier, (string) $verification->accountIdentifier());
        $this->assertSame($verificationType, $verification->verificationType());
        $this->assertSame(VerificationStatus::PENDING, $verification->status());
        $this->assertSame($applicantInfo, $verification->applicantInfo());
        $this->assertNull($verification->reviewedBy());
        $this->assertNull($verification->reviewedAt());
        $this->assertNull($verification->rejectionReason());
    }
}
