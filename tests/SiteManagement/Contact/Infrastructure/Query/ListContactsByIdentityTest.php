<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Infrastructure\Query\ListContactsByIdentity;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateUser;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListContactsByIdentityTest extends TestCase
{
    public function testUseCaseIsBoundToInfrastructureQuery(): void
    {
        $this->assertSame(ListContactsByIdentity::class, $this->app->make(ListContactsByIdentityInterface::class)::class);
    }

    #[Group('useDb')]
    public function testProcessReturnsOnlySpecifiedIdentityContactsForAdmin(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        $target = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::ADMIN]);
        $older = StrTestHelper::generateUuid();
        $newer = StrTestHelper::generateUuid();
        $this->insertContact($older, (string) $target, 'older@example.com', '2026-08-15 10:00:00');
        $this->insertContact($newer, (string) $target, 'newer@example.com', '2026-08-16 10:00:00');
        $this->insertContact(StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), 'other@example.com', '2026-08-17 10:00:00');

        $output = new ListContactsByIdentityOutput();
        $this->app->make(ListContactsByIdentityInterface::class)->process(new ListContactsByIdentityInput($requester, $target), $output);

        $this->assertSame([$newer, $older], array_column($output->toArray(), 'contactIdentifier'));
        $this->assertSame(['newer@example.com', 'older@example.com'], array_column($output->toArray(), 'email'));
    }

    #[Group('useDb')]
    public function testProcessRejectsNonAdmin(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::NONE]);

        $this->expectException(UnauthorizedException::class);
        $this->app->make(ListContactsByIdentityInterface::class)->process(
            new ListContactsByIdentityInput($requester, new IdentityIdentifier(StrTestHelper::generateUuid())),
            new ListContactsByIdentityOutput(),
        );
    }

    private function insertContact(string $id, string $identityIdentifier, string $email, string $createdAt): void
    {
        DB::table('contacts')->insert([
            'id' => $id,
            'identity_identifier' => $identityIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'email' => $this->app->make(EncryptionServiceInterface::class)->encrypt($email),
            'content' => 'お問い合わせ内容',
            'language' => 'ja',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
