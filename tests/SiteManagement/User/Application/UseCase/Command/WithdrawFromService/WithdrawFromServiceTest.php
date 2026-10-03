<?php

declare(strict_types=1);

namespace Tests\SiteManagement\User\Application\UseCase\Command\WithdrawFromService;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\SiteManagement\User\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\SiteManagement\User\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WithdrawFromServiceTest extends TestCase
{
    #[Group('useDb')]
    public function testDeletesServiceUserAndPreservesContactsAndRepliesAfterIdentityDeletion(): void
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

        $contactsBefore = DB::table('contacts')->orderBy('id')->get()->toArray();
        $repliesBefore = DB::table('contact_replies')->orderBy('id')->get()->toArray();

        $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identity), new WithdrawFromServiceOutput());

        $this->app()->make(IdentityRepositoryInterface::class)->delete($identity);

        $this->assertDatabaseMissing('identities', ['id' => (string) $identity]);
        $this->assertEquals($contactsBefore, DB::table('contacts')->orderBy('id')->get()->toArray());
        $this->assertEquals($repliesBefore, DB::table('contact_replies')->orderBy('id')->get()->toArray());
        $this->assertDatabaseMissing('site_management_users', ['identity_id' => (string) $identity]);
    }
}
