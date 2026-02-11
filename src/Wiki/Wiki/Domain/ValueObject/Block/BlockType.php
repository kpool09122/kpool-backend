<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

enum BlockType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case IMAGE_GALLERY = 'image_gallery';
    case EMBED = 'embed';
    case QUOTE = 'quote';
    case LIST = 'list';
    case TABLE = 'table';
    case PROFILE_CARD_LIST = 'profile_card_list';
}
