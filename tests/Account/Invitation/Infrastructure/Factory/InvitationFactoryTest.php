<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Account\Invitation\Infrastructure\Factory\InvitationFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class InvitationFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(InvitationFactoryInterface::class);
        $this->assertInstanceOf(InvitationFactory::class, $factory);
    }

    /**
     * 正常系: 正しくInvitationエンティティが作成できること
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('invitee@example.com');

        $factory = $this->app->make(InvitationFactoryInterface::class);
        $invitation = $factory->create(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $email,
        );

        $this->assertTrue(UuidValidator::isValid((string) $invitation->invitationIdentifier()));
        $this->assertSame($accountIdentifier, $invitation->accountIdentifier());
        $this->assertSame($inviterIdentityIdentifier, $invitation->invitedByIdentityIdentifier());
        $this->assertSame($email, $invitation->email());
        $this->assertSame(InvitationStatus::PENDING, $invitation->status());
        $this->assertNull($invitation->acceptedByIdentityIdentifier());
        $this->assertNull($invitation->acceptedAt());
        $this->assertNotNull($invitation->createdAt());
    }

    /**
     * 正常系: トークンが正しい形式で生成されること
     *
     * @throws BindingResolutionException
     */
    public function testCreateGeneratesValidToken(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('invitee@example.com');

        $factory = $this->app->make(InvitationFactoryInterface::class);
        $invitation = $factory->create(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $email,
        );

        $token = $invitation->token();
        $this->assertInstanceOf(InvitationToken::class, $token);
        $this->assertSame(InvitationToken::TOKEN_LENGTH, strlen((string) $token));
        $this->assertTrue(ctype_xdigit((string) $token));
    }

    /**
     * 正常系: 有効期限が24時間後に設定されること
     *
     * @throws BindingResolutionException
     */
    public function testCreateSetsExpirationTo24Hours(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('invitee@example.com');

        $factory = $this->app->make(InvitationFactoryInterface::class);
        $invitation = $factory->create(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $email,
        );

        $createdAt = $invitation->createdAt();
        $expiresAt = $invitation->expiresAt();

        $expectedExpiresAt = $createdAt->modify('+24 hours');
        $this->assertSame(
            $expectedExpiresAt->format('Y-m-d H:i'),
            $expiresAt->format('Y-m-d H:i')
        );
    }

    /**
     * 正常系: 作成直後の招待は期限切れではないこと
     *
     * @throws BindingResolutionException
     */
    public function testCreateInvitationIsNotExpired(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('invitee@example.com');

        $factory = $this->app->make(InvitationFactoryInterface::class);
        $invitation = $factory->create(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $email,
        );

        $this->assertFalse($invitation->isExpired());
        $this->assertTrue($invitation->isPending());
    }

    /**
     * 正常系: 複数回呼び出しても異なるトークンが生成されること
     *
     * @throws BindingResolutionException
     */
    public function testCreateGeneratesUniqueTokens(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('invitee@example.com');

        $factory = $this->app->make(InvitationFactoryInterface::class);

        $invitation1 = $factory->create($accountIdentifier, $inviterIdentityIdentifier, $email);
        $invitation2 = $factory->create($accountIdentifier, $inviterIdentityIdentifier, $email);

        $this->assertNotSame(
            (string) $invitation1->token(),
            (string) $invitation2->token()
        );
        $this->assertNotSame(
            (string) $invitation1->invitationIdentifier(),
            (string) $invitation2->invitationIdentifier()
        );
    }
}
