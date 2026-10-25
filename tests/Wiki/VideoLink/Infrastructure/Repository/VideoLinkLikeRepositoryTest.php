<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Repository;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkLikeFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkLikeRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class VideoLinkLikeRepositoryTest extends TestCase
{
    public function testSaveFindAndDeleteOnlyOwnLike(): void
    {
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video);
        $principal = $this->principal();
        $other = $this->principal();
        $repository = $this->app()->make(VideoLinkLikeRepositoryInterface::class);
        $factory = $this->app()->make(VideoLinkLikeFactoryInterface::class);
        self::assertNull($repository->findByVideoLinkAndPrincipal($video, $principal));
        $like = $factory->create($video, $principal);
        $repository->save($like);
        $repository->save($like);
        $repository->save($factory->create($video, $other));
        self::assertSame(2, DB::table('wiki_video_link_likes')->count());
        $restored = $repository->findByVideoLinkAndPrincipal($video, $principal);
        self::assertNotNull($restored);
        self::assertSame((string) $like->videoLinkLikeIdentifier(), (string) $restored->videoLinkLikeIdentifier());
        self::assertSame((string) $video, (string) $restored->videoLinkIdentifier());
        self::assertSame((string) $principal, (string) $restored->principalIdentifier());
        self::assertSame($like->createdAt()->getTimestamp(), $restored->createdAt()->getTimestamp());
        $repository->deleteByVideoLinkAndPrincipal($video, $principal);
        $repository->deleteByVideoLinkAndPrincipal($video, $principal);
        self::assertNull($repository->findByVideoLinkAndPrincipal($video, $principal));
        self::assertNotNull($repository->findByVideoLinkAndPrincipal($video, $other));
    }

    public function testSameYouTubeUrlOnDifferentVideoLinksHasIndependentLikes(): void
    {
        $first = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $second = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        foreach ([$first, $second] as $video) {
            CreateVideoLink::create((string) $video, ['url' => 'https://www.youtube.com/watch?v=Os_heh8vPfs']);
        }
        $principal = $this->principal();
        $factory = $this->app()->make(VideoLinkLikeFactoryInterface::class);
        $repository = $this->app()->make(VideoLinkLikeRepositoryInterface::class);
        $repository->save($factory->create($first, $principal));
        self::assertNull($repository->findByVideoLinkAndPrincipal($second, $principal));
        $repository->save($factory->create($second, $principal));
        self::assertSame(2, DB::table('wiki_video_link_likes')->count());
        $repository->deleteByVideoLinkAndPrincipal($first, $principal);
        self::assertNotNull($repository->findByVideoLinkAndPrincipal($second, $principal));
    }

    public function testDatabaseRejectsDuplicateVideoPrincipalPair(): void
    {
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video);
        $principal = $this->principal();
        $factory = $this->app()->make(VideoLinkLikeFactoryInterface::class);
        $repository = $this->app()->make(VideoLinkLikeRepositoryInterface::class);
        $repository->save($factory->create($video, $principal));
        $this->expectException(UniqueConstraintViolationException::class);
        $repository->save($factory->create($video, $principal));
    }

    /** @return array<string, array{string}> */
    public static function deletionPaths(): array
    {
        return ['single' => ['single'], 'resource' => ['resource'], 'automatic' => ['automatic']];
    }

    #[DataProvider('deletionPaths')]
    public function testEveryVideoDeletionPathCascadesLikes(string $path): void
    {
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $wiki = new WikiIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => (string) $wiki, 'resource_type' => 'talent', 'video_usage' => 'youtube_auto_view_count']);
        $otherVideo = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $otherVideo);
        $principal = $this->principal();
        $factory = $this->app()->make(VideoLinkLikeFactoryInterface::class);
        $repository = $this->app()->make(VideoLinkLikeRepositoryInterface::class);
        $repository->save($factory->create($video, $principal));
        $repository->save($factory->create($otherVideo, $principal));
        $videoLinkRepository = $this->app()->make(VideoLinkRepositoryInterface::class);
        match ($path) {
            'single' => $videoLinkRepository->delete($video),
            'resource' => $videoLinkRepository->deleteByResource(ResourceType::TALENT, $wiki),
            default => $videoLinkRepository->deleteAutoCollectedByResource(ResourceType::TALENT, $wiki),
        };
        self::assertNull($repository->findByVideoLinkAndPrincipal($video, $principal));
        self::assertNotNull($repository->findByVideoLinkAndPrincipal($otherVideo, $principal));
    }

    private function principal(): PrincipalIdentifier
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        CreatePrincipal::create($principal, $identity);

        return $principal;
    }
}
