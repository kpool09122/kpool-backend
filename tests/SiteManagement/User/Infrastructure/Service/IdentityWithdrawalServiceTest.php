<?php

declare(strict_types=1);

namespace Tests\SiteManagement\User\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Infrastructure\Service\IdentityWithdrawalService;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class IdentityWithdrawalServiceTest extends TestCase
{
    #[Group('useDb')]
    public function testDeletesPersonalContactsRepliesAndServiceUser(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $ownContact = StrTestHelper::generateUuid();
        $otherContact = StrTestHelper::generateUuid();
        foreach ([$ownContact, $otherContact] as $id) {
            DB::table('contacts')->insert(['id' => $id, 'category' => 1, 'identity_identifier' => $id === $ownContact ? (string) $identity : null, 'name' => 'Private name', 'email' => 'private@example.com', 'content' => 'Private content', 'language' => 'ja']);
        }
        DB::table('site_management_users')->insert(['id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $identity, 'role' => 'admin']);
        $retainedReply = StrTestHelper::generateUuid();
        foreach ([[$ownContact, null, StrTestHelper::generateUuid()], [$otherContact, (string) $identity, StrTestHelper::generateUuid()], [$otherContact, null, $retainedReply]] as [$contact, $author, $id]) {
            DB::table('contact_replies')->insert(['id' => $id, 'contact_id' => $contact, 'identity_identifier' => $author, 'content' => 'Private reply', 'to_email' => 'private@example.com']);
        }

        $this->app()->make(IdentityWithdrawalService::class)->withdraw($identity);

        $this->assertDatabaseMissing('contacts', ['id' => $ownContact]);
        $this->assertDatabaseHas('contacts', ['id' => $otherContact]);
        $this->assertDatabaseMissing('contact_replies', ['contact_id' => $ownContact]);
        $this->assertDatabaseMissing('contact_replies', ['identity_identifier' => (string) $identity]);
        $this->assertDatabaseHas('contact_replies', ['id' => $retainedReply]);
        $this->assertDatabaseMissing('site_management_users', ['identity_id' => (string) $identity]);
    }
}
