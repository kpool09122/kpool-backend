<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateUser;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetContactDetailTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsSpecifiedIdentityContactForAdmin(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        $target = new IdentityIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = StrTestHelper::generateUuid();
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::ADMIN]);
        $this->insertContact($contactIdentifier, (string) $target);

        $output = new GetContactDetailOutput();
        $this->app->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($requester, $target, new ContactIdentifier($contactIdentifier)),
            $output,
        );

        $this->assertSame($contactIdentifier, $output->toArray()['contactIdentifier']);
    }

    #[Group('useDb')]
    public function testProcessRejectsNonAdmin(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::NONE]);

        $this->expectException(UnauthorizedException::class);
        $this->app->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($requester, new IdentityIdentifier(StrTestHelper::generateUuid()), new ContactIdentifier(StrTestHelper::generateUuid())),
            new GetContactDetailOutput(),
        );
    }

    #[Group('useDb')]
    public function testProcessRejectsContactNotOwnedBySpecifiedIdentity(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::ADMIN]);
        $contactIdentifier = StrTestHelper::generateUuid();
        $this->insertContact($contactIdentifier, StrTestHelper::generateUuid());

        $this->expectException(ContactNotFoundException::class);
        $this->app->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($requester, new IdentityIdentifier(StrTestHelper::generateUuid()), new ContactIdentifier($contactIdentifier)),
            new GetContactDetailOutput(),
        );
    }

    private function insertContact(string $id, string $identityIdentifier): void
    {
        DB::table('contacts')->insert(['id' => $id, 'identity_identifier' => $identityIdentifier, 'category' => Category::SUGGESTIONS->value, 'name' => '問い合わせ者', 'email' => $this->app->make(EncryptionServiceInterface::class)->encrypt('contact@example.com'), 'content' => 'お問い合わせ内容', 'language' => 'ja', 'created_at' => '2026-08-16 10:00:00', 'updated_at' => '2026-08-16 10:00:00']);
    }
}
