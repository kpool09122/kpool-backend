<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Random\RandomException;

interface OAuthStateGeneratorInterface
{
    /**
     * @return OAuthStateSession
     * @throws RandomException
     */
    public function generate(): OAuthStateSession;
}
