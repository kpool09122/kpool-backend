<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItem;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemPosition;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\Shared\Infrastructure\Service\VideoThumbnailService;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class VideoThumbnailServiceTest extends TestCase
{
    #[DataProvider('cleanupPaths')]
    public function testDeletesUnusedCoverAfterBulkAndPrincipalCascade(string $kind): void
    {
        Storage::fake('public');
        config()->set('filesystems.image_disk', 'public');
        $path = new ImagePath('images/' . $kind . '.webp');
        Storage::disk('public')->put((string) $path, 'image');
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreatePrincipal::create($owner, $identity);
        $account = DB::table('wiki_principals')->where('id', (string) $owner)->value('account_id');
        $this->app()->make(VideoThumbnailService::class)->track($path);
        if ($kind === 'bulk') {
            $wiki = new WikiIdentifier(StrTestHelper::generateUuid());
            $video = StrTestHelper::generateUuid();
            CreateVideoLink::create($video, ['wiki_id' => (string) $wiki]);
            DB::table('wiki_video_links')->where('id', $video)->update(['thumbnail_path' => (string) $path]);
            $this->app()->make(VideoLinkRepositoryInterface::class)->deleteByResource(ResourceType::TALENT, $wiki);
        } else {
            $playlist = PlaylistTestHelper::playlist(owner: $owner);
            $item = new PlaylistItem($playlist->playlistIdentifier(), null, VideoProvider::TIKTOK, new ExternalVideoId('6718335390845095173'), new TikTokVideoUrl('https://www.tiktok.com/@scout2015/video/6718335390845095173'), new VideoTitle('Saved'), null, null, new PlaylistItemPosition(0), $path);
            $this->app()->make(PlaylistRepositoryInterface::class)->save(PlaylistTestHelper::playlist($playlist->playlistIdentifier(), $owner, [$item]));
            $this->app()->make(PrincipalRepositoryInterface::class)->deleteByIdentityIdentifier($identity);
        }
        Storage::disk('public')->assertExists((string) $path);
        DB::commit();
        Storage::disk('public')->assertMissing((string) $path);
        DB::table('identities')->where('id', (string) $identity)->delete();
        DB::table('accounts')->where('id', $account)->delete();
        DB::beginTransaction();
    }

    /** @return list<array{string}> */
    public static function cleanupPaths(): array
    {
        return [['bulk'], ['cascade']];
    }

    public function testKeepsSharedCoverThroughSourceDeletionAndDeletesItAfterLastPlaylistReference(): void
    {
        Storage::fake('public');
        config()->set('filesystems.image_disk', 'public');
        $path = new ImagePath('images/shared.webp');
        Storage::disk('public')->put((string) $path, 'image');
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreatePrincipal::create($owner, $identity);
        $account = DB::table('wiki_principals')->where('id', (string) $owner)->value('account_id');
        $source = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $source, ['url' => 'https://www.tiktok.com/@scout2015/video/6718335390845095173']);
        DB::table('wiki_video_links')->where('id', (string) $source)->update(['thumbnail_path' => (string) $path]);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $playlists = [];
        foreach ([0, 1] as $number) {
            $playlist = PlaylistTestHelper::playlist(owner: $owner);
            $item = new PlaylistItem($playlist->playlistIdentifier(), $source, VideoProvider::TIKTOK, new ExternalVideoId('6718335390845095173'), new TikTokVideoUrl('https://www.tiktok.com/@scout2015/video/6718335390845095173'), new VideoTitle('Saved TikTok'), null, null, new PlaylistItemPosition(0), $path);
            $playlist = PlaylistTestHelper::playlist($playlist->playlistIdentifier(), $owner, [$item, PlaylistTestHelper::item($playlist->playlistIdentifier(), 1)]);
            $repository->save($playlist);
            $playlists[] = $playlist;
        }
        DB::commit();
        DB::beginTransaction();
        $this->app()->make(VideoLinkRepositoryInterface::class)->delete($source);
        DB::commit();
        Storage::disk('public')->assertExists((string) $path);
        DB::beginTransaction();
        $restored = $repository->findByIdAndPrincipal($playlists[0]->playlistIdentifier(), $owner);
        self::assertNotNull($restored);
        self::assertNull($restored->items()[0]->videoLinkIdentifier());
        self::assertSame('tiktok', $restored->items()[0]->provider()->value);
        self::assertSame((string) $path, (string) $restored->items()[0]->thumbnailPath());
        $repository->save(PlaylistTestHelper::playlist($restored->playlistIdentifier(), $owner, [PlaylistTestHelper::item($restored->playlistIdentifier(), 0)]));
        DB::commit();
        Storage::disk('public')->assertExists((string) $path);
        DB::beginTransaction();
        $repository->deleteByIdAndPrincipal($playlists[1]->playlistIdentifier(), $owner);
        Storage::disk('public')->assertExists((string) $path);
        DB::commit();
        Storage::disk('public')->assertMissing((string) $path);
        DB::beginTransaction();
        DB::table('identities')->where('id', (string) $identity)->delete();
        DB::table('accounts')->where('id', $account)->delete();
        DB::commit();
        DB::beginTransaction();
    }

    public function testRetainsFailedDeletionsForRetry(): void
    {
        /** @var ImageServiceInterface&Mockery\MockInterface $images */
        $images = Mockery::mock(ImageServiceInterface::class);
        $path = new ImagePath('images/retry.webp');
        $images->shouldReceive('delete')->twice()->with(Mockery::on(static fn (ImagePath $candidate): bool => (string) $candidate === (string) $path))->andReturn(false, true);
        $service = new VideoThumbnailService($images);
        $service->track($path);
        $service->releaseUnused();
        DB::commit();
        self::assertTrue(DB::table('wiki_video_thumbnails')->where('path', (string) $path)->exists());
        $service->releaseUnused();
        self::assertFalse(DB::table('wiki_video_thumbnails')->where('path', (string) $path)->exists());
        DB::beginTransaction();
    }

    public function testCompensatesNewImageAfterRegistrationRollback(): void
    {
        Storage::fake('public');
        config()->set('filesystems.image_disk', 'public');
        $path = new ImagePath('images/compensate.webp');
        Storage::disk('public')->put((string) $path, 'new');
        $service = $this->app()->make(VideoThumbnailService::class);
        $service->track($path);
        DB::rollBack();
        $service->track($path);
        $service->releaseUnused();
        Storage::disk('public')->assertMissing((string) $path);
        self::assertSame(0, DB::table('wiki_video_thumbnails')->count());
        DB::beginTransaction();
    }

    public function testDeletesOnlyUnreferencedImagesAfterCommit(): void
    {
        Storage::fake('s3');
        config()->set('filesystems.image_disk', 's3');
        $path = new ImagePath('images/cover.webp');
        Storage::disk('s3')->put((string) $path, 'image');
        $service = $this->app()->make(VideoThumbnailService::class);
        $service->track($path);
        $id = StrTestHelper::generateUuid();
        CreateVideoLink::create($id);
        DB::table('wiki_video_links')->where('id', $id)->update(['thumbnail_path' => (string) $path]);
        $service->releaseUnused();
        DB::commit();
        Storage::disk('s3')->assertExists((string) $path);
        DB::beginTransaction();
        DB::table('wiki_video_links')->where('id', $id)->delete();
        $service->releaseUnused();
        Storage::disk('s3')->assertExists((string) $path);
        DB::commit();
        Storage::disk('s3')->assertMissing((string) $path);
        self::assertFalse(DB::table('wiki_video_thumbnails')->where('path', (string) $path)->exists());
        DB::table('wiki_video_thumbnails')->delete();
        DB::beginTransaction();
    }

    public function testRollbackDoesNotDeleteAnImage(): void
    {
        Storage::fake('s3');
        config()->set('filesystems.image_disk', 's3');
        $path = new ImagePath('images/rollback.webp');
        Storage::disk('s3')->put((string) $path, 'image');
        $service = $this->app()->make(VideoThumbnailService::class);
        $service->track($path);
        $service->releaseUnused();
        DB::rollBack();
        Storage::disk('s3')->assertExists((string) $path);
        DB::beginTransaction();
    }
}
