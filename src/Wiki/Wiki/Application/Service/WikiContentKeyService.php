<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentInterface;

final readonly class WikiContentKeyService
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function assignMissingKeys(SectionContentCollection $sections): SectionContentCollection
    {
        return $sections->map(function (SectionContentInterface $content): SectionContentInterface {
            if ($content instanceof Section) {
                $contents = $this->assignMissingKeys($content->contents());
                if ($contents !== $content->contents()) {
                    $content = $content->withContents($contents);
                }
            }

            return $content->key() === null ? $content->withKey($this->uuidGenerator->generate()) : $content;
        });
    }
}
