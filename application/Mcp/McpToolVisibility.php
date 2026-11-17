<?php

declare(strict_types=1);

namespace Application\Mcp;

use Application\Http\Context\AccountResolver;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Delegation\Application\Exception\DelegationUnavailableException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class McpToolVisibility
{
    public function __construct(
        private AccountResolver $accountResolver,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $definitions
     * @return list<array<string, mixed>>
     */
    public function filter(array $definitions, ValidatedAuthorizationToken $token): array
    {
        $attributes = request()->attributes;
        $previous = $attributes->get(ApiAuthenticationContext::class);
        $attributes->set(ApiAuthenticationContext::class, new ApiAuthenticationContext($token, $token->expiresAt));

        try {
            try {
                $accountContext = $this->accountResolver->resolve($token->connection->identityIdentifier());
                $principal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier(
                    $accountContext->originalIdentityIdentifier(),
                    $accountContext->principal()->accountIdentifier(),
                );
            } catch (AccountNotFoundException|DelegationUnavailableException) {
                $principal = null;
            }

            return array_values(array_filter($definitions, function (array $definition) use ($principal): bool {
                $action = match ($definition['name']) {
                    'list_managed_wiki_images' => Action::LIST_MANAGED_IMAGES,
                    'update_wiki_image_metadata' => Action::UPDATE_IMAGE_METADATA,
                    default => null,
                };

                return $action === null || ($principal !== null
                    && $this->policyEvaluator->evaluate($principal, $action, new Resource(ResourceType::IMAGE)));
            }));
        } finally {
            if ($previous === null) {
                $attributes->remove(ApiAuthenticationContext::class);
            } else {
                $attributes->set(ApiAuthenticationContext::class, $previous);
            }
        }
    }
}
