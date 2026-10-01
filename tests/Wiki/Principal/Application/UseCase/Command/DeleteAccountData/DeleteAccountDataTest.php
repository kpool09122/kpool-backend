<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DeleteAccountData;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData\DeleteAccountDataInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData\DeleteAccountDataInterface;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData\DeleteAccountDataOutput;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteAccountDataTest extends TestCase
{
    #[Group('useDb')]
    public function testUnmarksAllOwnedWikisAndPreservesContentAndOtherOwners(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        CreateAccount::create((string) $otherAccountIdentifier);
        $ownedWikiIds = [];
        foreach (['ko', 'ja'] as $language) {
            $wikiId = StrTestHelper::generateUuid();
            $ownedWikiIds[] = $wikiId;
            CreateWiki::create($wikiId, 'group', [
                'owner_account_id' => (string) $accountIdentifier,
                'language' => $language,
                'slug' => 'gr-owned-' . $wikiId,
                'title' => 'Retained title',
                'version' => 3,
            ], ['name' => 'Retained group name']);
        }
        $otherWikiId = StrTestHelper::generateUuid();
        CreateWiki::create($otherWikiId, 'group', ['owner_account_id' => (string) $otherAccountIdentifier, 'slug' => 'gr-other-' . $otherWikiId]);
        $unownedWikiId = StrTestHelper::generateUuid();
        CreateWiki::create($unownedWikiId, 'group', ['slug' => 'gr-unowned-' . $unownedWikiId]);

        $this->app()->make(DeleteAccountDataInterface::class)->process(
            new DeleteAccountDataInput($accountIdentifier),
            new DeleteAccountDataOutput(),
        );

        $wikiRepository = $this->app()->make(WikiRepositoryInterface::class);
        $this->assertSame([], $wikiRepository->findByOwnerAccountIdentifier($accountIdentifier));
        foreach ($ownedWikiIds as $wikiId) {
            $wiki = $wikiRepository->findById(new WikiIdentifier($wikiId));
            $this->assertNotNull($wiki);
            $this->assertFalse($wiki->isOfficial());
            $this->assertNull($wiki->ownerAccountIdentifier());
            $this->assertDatabaseHas('wikis', ['id' => $wikiId, 'title' => 'Retained title', 'version' => 3]);
            $this->assertDatabaseHas('wiki_group_basics', ['wiki_id' => $wikiId, 'name' => 'Retained group name']);
        }
        $this->assertDatabaseHas('wikis', ['id' => $otherWikiId, 'owner_account_id' => (string) $otherAccountIdentifier]);
        $this->assertDatabaseHas('wikis', ['id' => $unownedWikiId, 'owner_account_id' => null]);
    }
}
