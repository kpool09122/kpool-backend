<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Query;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Infrastructure\Query\ListContactsByIdentity;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\SiteManagementAuthorization;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListContactsByIdentityTest extends TestCase
{
    public function testUseCaseIsBoundToInfrastructureQuery(): void
    {
        $this->assertSame(ListContactsByIdentity::class, $this->app()->make(ListContactsByIdentityInterface::class)::class);
    }

    #[Group('useDb')]
    public function testProcessReturnsOnlySpecifiedIdentityContactsForAdmin(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        $target = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, true);
        $older = StrTestHelper::generateUuid();
        $newer = StrTestHelper::generateUuid();
        $this->insertContact($older, (string) $target, 'older@example.com', '2026-08-15 10:00:00');
        $this->insertContact($newer, (string) $target, 'newer@example.com', '2026-08-16 10:00:00');
        $this->insertContact(StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), 'other@example.com', '2026-08-17 10:00:00');

        $output = new ListContactsByIdentityOutput();
        $this->app()->make(ListContactsByIdentityInterface::class)->process(new ListContactsByIdentityInput($principalIdentifier, $target), $output);

        $this->assertSame([$newer, $older], array_column($output->toArray(), 'contactIdentifier'));
        $this->assertSame([[], []], array_column($output->toArray(), 'replyIdentifiers'));
        $this->assertSame([
            (new DateTimeImmutable('2026-08-16 10:00:00'))->format(DateTimeInterface::ATOM),
            (new DateTimeImmutable('2026-08-15 10:00:00'))->format(DateTimeInterface::ATOM),
        ], array_column($output->toArray(), 'createdAt'));
    }

    #[Group('useDb')]
    public function testProcessRejectsContactWithoutViewPermission(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, false);

        $target = new IdentityIdentifier(StrTestHelper::generateUuid());
        $this->insertContact(StrTestHelper::generateUuid(), (string) $target, 'denied@example.com', '2026-08-17 10:00:00');
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(ListContactsByIdentityInterface::class)->process(
            new ListContactsByIdentityInput($principalIdentifier, $target),
            new ListContactsByIdentityOutput(),
        );
    }

    #[Group('useDb')]
    public function testGeneralPolicyAllowsOwnContactsAndRejectsAnotherIdentityContacts(): void
    {
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreateAccount::create((string) $account);
        $provision = new ProvisionPrincipalOutput();
        $this->app()->make(ProvisionPrincipalInterface::class)->process(new ProvisionPrincipalInput($identity, $account), $provision);
        $principal = $provision->principal();
        $this->assertNotNull($principal);
        $input = new ListContactsByIdentityInput($principal->principalIdentifier(), $identity);
        $query = $this->app()->make(ListContactsByIdentityInterface::class);
        $empty = new ListContactsByIdentityOutput();
        $query->process($input, $empty);
        $this->assertSame([], $empty->toArray());

        $own = StrTestHelper::generateUuid();
        $this->insertContact($own, (string) $identity, 'own@example.com', '2026-08-17 10:00:00');
        $other = new IdentityIdentifier(StrTestHelper::generateUuid());
        $this->insertContact(StrTestHelper::generateUuid(), (string) $other, 'other@example.com', '2026-08-16 10:00:00');
        $output = new ListContactsByIdentityOutput();
        $query->process($input, $output);
        $this->assertSame([$own], array_column($output->toArray(), 'contactIdentifier'));

        $this->expectException(UnauthorizedException::class);
        $query->process(new ListContactsByIdentityInput($principal->principalIdentifier(), $other), new ListContactsByIdentityOutput());
    }

    private function insertContact(string $id, string $identityIdentifier, string $email, string $createdAt): void
    {
        DB::table('contacts')->insert([
            'id' => $id,
            'identity_identifier' => $identityIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'email' => $this->app()->make(EncryptionServiceInterface::class)->encrypt($email),
            'content' => 'お問い合わせ内容',
            'language' => 'ja',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
