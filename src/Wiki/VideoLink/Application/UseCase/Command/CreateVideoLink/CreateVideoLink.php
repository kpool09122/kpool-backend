<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use InvalidArgumentException;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\VideoLink\Application\Exception\VideoMetadataUnavailableException;
use Source\Wiki\VideoLink\Application\Service\VideoMetadataServiceInterface;
use Source\Wiki\VideoLink\Domain\Exception\VideoLinkAlreadyExistsException;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

readonly class CreateVideoLink implements CreateVideoLinkInterface
{
    public function __construct(
        private VideoLinkFactoryInterface $videoLinkFactory,
        private VideoLinkRepositoryInterface $videoLinkRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private WikiRepositoryInterface $wikiRepository,
        private VideoMetadataServiceInterface $videoMetadataService,
    ) {
    }

    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws WikiNotFoundException
     * @throws VideoMetadataUnavailableException
     * @throws InvalidArgumentException
     * @throws VideoLinkAlreadyExistsException
     */
    public function process(CreateVideoLinkInputPort $input, CreateVideoLinkOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $wiki = $this->wikiRepository->findById($input->wikiIdentifier());
        if ($wiki === null || $wiki->resourceType() !== $input->resourceType()) {
            throw new WikiNotFoundException();
        }
        if (! $this->policyEvaluator->evaluate($principal, Action::CREATE_VIDEO_LINK, new Resource(type: $wiki->resourceType()))) {
            throw new DisallowedException();
        }
        $url = $input->url();
        $duration = $input->durationSeconds();
        $thumbnail = $input->thumbnailUrl();
        $thumbnailPath = null;
        if ($url instanceof TikTokVideoUrl) {
            $metadata = $this->videoMetadataService->resolve((string) $url);
            $url = $metadata->url;
            $duration = $metadata->durationSeconds;
            $thumbnail = null;
            $thumbnailPath = $metadata->thumbnailPath;
            $output->output($thumbnailPath);
        }
        if ($this->videoLinkRepository->findByResourceAndUrls($input->resourceType(), $input->wikiIdentifier(), [(string) $url]) !== []) {
            throw new VideoLinkAlreadyExistsException();
        }
        $videoLink = $this->videoLinkFactory->create(
            $input->resourceType(),
            $input->wikiIdentifier(),
            $url,
            $input->videoUsage(),
            $input->title(),
            $thumbnail,
            $input->publishedAt(),
            $duration,
            $input->principalIdentifier(),
            $thumbnailPath,
        );
        $this->videoLinkRepository->save($videoLink);
    }
}
