<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Service;

use Source\Wiki\Principal\Domain\Entity\Principal;

interface PrincipalWikiScopeResolverInterface
{
    /** @return string[] */
    public function agencyWikiIdentifiers(Principal $principal): array;

    /** @return string[] */
    public function groupWikiIdentifiers(Principal $principal): array;

    /** @return string[] */
    public function talentGroupWikiIdentifiers(Principal $principal): array;

    /** @return string[] */
    public function talentWikiIdentifiers(Principal $principal): array;

    /** @return string[] */
    public function affiliatedTalentWikiIdentifiers(Principal $principal): array;
}
