<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\ValueObject\SongName;

interface DraftSongFactoryInterface
{
    /**
     * @param PrincipalIdentifier|null $editorIdentifier
     * @param Slug $slug
     * @param Language $language
     * @param SongName $name
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftSong
     */
    public function create(
        ?PrincipalIdentifier      $editorIdentifier,
        Slug                      $slug,
        Language                  $language,
        SongName                  $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftSong;
}
