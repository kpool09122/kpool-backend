<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\Action;

class ActionTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'CREATE' => 'create',
            'CREATE_PLAYLIST' => 'create_playlist',
            'LIST_MY_PLAYLISTS' => 'list_my_playlists',
            'READ' => 'read',
            'EDIT' => 'edit',
            'SUBMIT' => 'submit',
            'WITHDRAW' => 'withdraw',
            'APPROVE' => 'approve',
            'REJECT' => 'reject',
            'TRANSLATE' => 'translate',
            'PUBLISH' => 'publish',
            'ROLLBACK' => 'rollback',
            'MERGE' => 'merge',
            'AUTOMATIC_CREATE' => 'automatic_create',
            'CREATE_VIDEO_LINK' => 'save_video_links',
            'DELETE_VIDEO_LINK' => 'delete_video_link',
            'LIKE_VIDEO_LINK' => 'like_video_link',
            'UNLIKE_VIDEO_LINK' => 'unlike_video_link',
            'SEARCH_FREE_MATERIALS' => 'search_free_materials',
            'DELETE' => 'delete',
            'HIDE' => 'hide',
            'UNHIDE' => 'unhide',
            'OFFICIAL_CERTIFICATION_REQUEST' => 'official_certification_request',
            'OFFICIAL_CERTIFICATION_MY_READ' => 'official_certification_my_read',
            'OFFICIAL_CERTIFICATION_READ' => 'official_certification_read',
            'OFFICIAL_CERTIFICATION_APPROVE' => 'official_certification_approve',
            'OFFICIAL_CERTIFICATION_REJECT' => 'official_certification_reject',
            'OFFICIAL_CERTIFICATION_OWNED_WIKI_SYNC' => 'official_certification_owned_wiki_sync',
            'RELATED_WIKI_LIST' => 'related-wiki-list',
            'PRINCIPAL_GROUP_MANAGE' => 'principal-group-manage',
        ], array_column(Action::cases(), 'value', 'name'));
    }
}
