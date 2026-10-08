<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Query;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\SiteManagementAuthorization;
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
        $principalIdentifier = SiteManagementAuthorization::bind($requester, true);
        $this->insertContact($contactIdentifier, (string) $target);

        $output = new GetContactDetailOutput();
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principalIdentifier, $target, new ContactIdentifier($contactIdentifier)),
            $output,
        );

        $this->assertSame($contactIdentifier, $output->toArray()['contactIdentifier']);
    }

    #[Group('useDb')]
    public function testProcessRejectsNonAdmin(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, false);

        $this->expectException(UnauthorizedException::class);
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new ContactIdentifier(StrTestHelper::generateUuid())),
            new GetContactDetailOutput(),
        );
    }

    #[Group('useDb')]
    public function testProcessRejectsContactNotOwnedBySpecifiedIdentity(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, true);
        $contactIdentifier = StrTestHelper::generateUuid();
        $this->insertContact($contactIdentifier, StrTestHelper::generateUuid());

        $this->expectException(ContactNotFoundException::class);
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new ContactIdentifier($contactIdentifier)),
            new GetContactDetailOutput(),
        );
    }

    /** @return array<string, array{bool, bool, bool, bool}> */
    public static function policyScenarios(): array
    {
        return [
            'general can view own contact' => [true, false, false, true],
            'general cannot view another contact' => [false, false, false, false],
            'own contact deny overrides administrator allow' => [true, true, true, false],
            'own contact deny does not affect another contact' => [false, true, true, true],
        ];
    }

    #[Group('useDb')]
    #[DataProvider('policyScenarios')]
    public function testProcessEvaluatesPoliciesForTheTargetContact(bool $ownContact, bool $administrator, bool $denyOwnContact, bool $allowed): void
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
        if ($administrator) {
            SiteManagementAuthorization::grantAdministrator($principal);
        }
        if ($denyOwnContact) {
            DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update([
                'statements' => json_encode([[
                    'effect' => 'deny',
                    'actions' => ['contact:view'],
                    'resource_types' => ['contact'],
                    'condition' => 'own_contact',
                ]], JSON_THROW_ON_ERROR),
            ]);
        }
        $owner = $ownContact ? $identity : new IdentityIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = StrTestHelper::generateUuid();
        $this->insertContact($contactIdentifier, (string) $owner);
        if (! $allowed) {
            $this->expectException(UnauthorizedException::class);
        }
        $output = new GetContactDetailOutput();
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principal->principalIdentifier(), $owner, new ContactIdentifier($contactIdentifier)),
            $output,
        );
        if ($allowed) {
            $this->assertSame($contactIdentifier, $output->toArray()['contactIdentifier']);
        }
    }

    private function insertContact(string $id, string $identityIdentifier): void
    {
        DB::table('contacts')->insert(['id' => $id, 'identity_identifier' => $identityIdentifier, 'category' => Category::SUGGESTIONS->value, 'name' => '問い合わせ者', 'email' => $this->app()->make(EncryptionServiceInterface::class)->encrypt('contact@example.com'), 'content' => 'お問い合わせ内容', 'language' => 'ja', 'created_at' => '2026-08-16 10:00:00', 'updated_at' => '2026-08-16 10:00:00']);
    }
}
