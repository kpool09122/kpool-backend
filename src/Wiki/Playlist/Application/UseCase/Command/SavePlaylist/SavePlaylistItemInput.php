<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist;

use InvalidArgumentException;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;

readonly class SavePlaylistItemInput
{
    /** @throws InvalidArgumentException */
    public function __construct(
        public ?VideoProvider $provider = null,
        public ?ExternalVideoId $externalVideoId = null,
        public ?VideoLinkIdentifier $videoLinkIdentifier = null,
    ) {
        if ($provider !== null && $externalVideoId !== null) {
            $valid = match ($provider) {
                VideoProvider::YOUTUBE => ExternalVideoId::isValidYouTubeId((string) $externalVideoId),
                VideoProvider::TIKTOK => ExternalVideoId::isValidTikTokId((string) $externalVideoId),
            };
            if (! $valid) {
                throw new InvalidArgumentException('Invalid provider video identifier.');
            }
        }
        if (($provider === null) !== ($externalVideoId === null)
            || ($provider !== null) === ($videoLinkIdentifier !== null)) {
            throw new InvalidArgumentException('Specify either an existing provider/externalVideoId pair or a new videoLinkIdentifier.');
        }
    }
}
