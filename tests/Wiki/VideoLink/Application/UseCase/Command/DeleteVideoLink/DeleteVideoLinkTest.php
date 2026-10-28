<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink\DeleteVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink\DeleteVideoLinkInterface;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoWatchRequestRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteVideoLinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $repository = Mockery::mock(VideoWatchRequestRepositoryInterface::class);
        $repository->shouldReceive('findLatestByVideoLink')->andReturn(null);
        $this->app()->instance(VideoWatchRequestRepositoryInterface::class, $repository);
    }

    public function testCreatorCanDeleteManualVideo(): void
    {
        $input = $this->input();
        $this->principal($input);
        $this->video($input, VideoUsage::MUSIC_VIDEO, new PrincipalIdentifier((string) $input->principalIdentifier()), true);
        $this->app()->make(DeleteVideoLinkInterface::class)->process($input);
    }

    /** @return array<string, array{VideoUsage, bool}> */
    public static function autoVideos(): array
    {
        $cases = [];
        foreach ([VideoUsage::YOUTUBE_AUTO_VIEW_COUNT, VideoUsage::YOUTUBE_AUTO_LIKE_COUNT, VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR] as $usage) {
            $cases[$usage->value . ' without creator'] = [$usage, false];
            $cases[$usage->value . ' with other creator'] = [$usage, true];
        }

        return $cases;
    }

    #[DataProvider('autoVideos')]
    public function testCollaboratorCanDeleteAutoVideo(VideoUsage $usage, bool $hasCreator): void
    {
        $input = $this->input();
        $this->principal($input);
        $this->video($input, $usage, $hasCreator ? new PrincipalIdentifier(StrTestHelper::generateUuid()) : null, true);
        $this->app()->make(DeleteVideoLinkInterface::class)->process($input);
    }

    /** @return iterable<string, array{VideoUsage}> */
    public static function allVideos(): iterable
    {
        foreach (VideoUsage::cases() as $usage) {
            yield $usage->value => [$usage];
        }
    }

    #[DataProvider('allVideos')]
    public function testPrincipalWithoutDeletionPermissionCannotDeleteVideoEvenAsCreator(VideoUsage $usage): void
    {
        $input = $this->input();
        $this->principal($input);
        $this->video($input, $usage, $input->principalIdentifier(), false, false);
        $this->expectException(DisallowedException::class);
        $this->app()->make(DeleteVideoLinkInterface::class)->process($input);
    }

    /** @return array<string, array{VideoUsage}> */
    public static function manualVideos(): array
    {
        $cases = [];
        foreach (VideoUsage::cases() as $usage) {
            if ($usage->isManual()) {
                $cases[$usage->value] = [$usage];
            }
        }

        return $cases;
    }

    #[DataProvider('manualVideos')]
    public function testOtherPrincipalCannotDeleteManualVideo(VideoUsage $usage): void
    {
        $input = $this->input();
        $this->principal($input);
        $this->video($input, $usage, new PrincipalIdentifier(StrTestHelper::generateUuid()), false);
        $this->expectException(DisallowedException::class);
        $this->app()->make(DeleteVideoLinkInterface::class)->process($input);
    }

    public function testManualVideoWithoutCreatorIsNotTreatedAsAutoCollected(): void
    {
        $input = $this->input();
        $this->principal($input);
        $this->video($input, VideoUsage::OTHER, null, false);
        $this->expectException(DisallowedException::class);
        $this->app()->make(DeleteVideoLinkInterface::class)->process($input);
    }

    public function testMissingPrincipalCannotDeleteOrLookupVideo(): void
    {
        $input = $this->input();
        $this->principal($input, false);
        $repository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $repository->shouldNotReceive('findById');
        $repository->shouldNotReceive('delete');
        $this->app()->instance(VideoLinkRepositoryInterface::class, $repository);
        $this->expectException(PrincipalNotFoundException::class);
        $this->app()->make(DeleteVideoLinkInterface::class)->process($input);
    }

    public function testMissingVideoCannotBeDeleted(): void
    {
        $input = $this->input();
        $this->principal($input);
        $repository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($input->videoLinkIdentifier())->andReturn(null);
        $repository->shouldNotReceive('delete');
        $this->app()->instance(VideoLinkRepositoryInterface::class, $repository);
        $this->expectException(VideoLinkNotFoundException::class);
        $this->app()->make(DeleteVideoLinkInterface::class)->process($input);
    }

    private function input(): DeleteVideoLinkInput
    {
        return new DeleteVideoLinkInput(new VideoLinkIdentifier(StrTestHelper::generateUuid()), new PrincipalIdentifier(StrTestHelper::generateUuid()));
    }

    private function principal(DeleteVideoLinkInput $input, bool $exists = true): void
    {
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($input->principalIdentifier())->andReturn($exists ? new Principal($input->principalIdentifier(), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid())) : null);
        $this->app()->instance(PrincipalRepositoryInterface::class, $repository);
        $policy = Mockery::mock(PolicyEvaluatorInterface::class);
        $policy->shouldNotReceive('evaluate');
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
    }

    private function video(DeleteVideoLinkInput $input, VideoUsage $usage, ?PrincipalIdentifier $creator, bool $allowed, bool $hasDeletionPermission = true): void
    {
        $policy = Mockery::mock(PolicyEvaluatorInterface::class);
        $policy->shouldReceive('evaluate')->once()->with(
            Mockery::on(fn (Principal $principal): bool => (string) $principal->principalIdentifier() === (string) $input->principalIdentifier()),
            Action::DELETE_VIDEO_LINK,
            Mockery::on(fn (Resource $resource): bool => $resource->type() === ResourceType::TALENT),
        )->andReturn($hasDeletionPermission);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $video = $this->app()->make(VideoLinkFactoryInterface::class)->create(ResourceType::TALENT, new WikiIdentifier(StrTestHelper::generateUuid()), new ExternalContentLink('https://www.youtube.com/watch?v=test'), $usage, new VideoTitle('Video'), creatorPrincipalIdentifier: $creator);
        $repository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($input->videoLinkIdentifier())->andReturn($video);
        if ($allowed) {
            $repository->shouldReceive('delete')->once()->with($input->videoLinkIdentifier());
        } else {
            $repository->shouldNotReceive('delete');
        }
        $this->app()->instance(VideoLinkRepositoryInterface::class, $repository);
    }
}
