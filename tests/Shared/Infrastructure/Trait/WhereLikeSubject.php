<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Trait;

use Source\Shared\Infrastructure\Trait\WhereLike;

final class WhereLikeSubject
{
    use WhereLike {
        whereLike as public;
        whereStartsWith as public;
    }
}
