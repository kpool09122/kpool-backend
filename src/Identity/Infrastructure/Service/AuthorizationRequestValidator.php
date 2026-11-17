<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Models\Identity\McpClient;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class AuthorizationRequestValidator implements AuthorizationRequestValidatorInterface
{
    public function validate(AuthorizationCodeParameters $authorizationCodeParameters): string
    {
        $client = McpClient::query()->find((string) $authorizationCodeParameters->clientIdentifier);
        if ($client === null || $client->revoked || ! $client->hasGrantType('authorization_code')) {
            throw new InvalidAuthorizationRequestException();
        }

        $redirectUri = $authorizationCodeParameters->redirectUri;
        if (! in_array($redirectUri, $client->redirect_uris, true) || parse_url($redirectUri, PHP_URL_FRAGMENT) !== null) {
            throw new InvalidAuthorizationRequestException();
        }

        $scopes = array_map(static fn (AuthorizationScope $scope): string => $scope->value, $authorizationCodeParameters->requestedScopes);
        if ($scopes === [] || count(array_unique($scopes)) !== count($scopes)) {
            throw new InvalidAuthorizationRequestException();
        }

        $resource = $authorizationCodeParameters->resource;
        if ($resource === '' || $resource !== config()->string('mcp_oauth.resource') || config()->string('mcp_oauth.issuer') === '') {
            throw new InvalidAuthorizationRequestException();
        }

        if (preg_match('/^[A-Za-z0-9_-]{43}$/D', $authorizationCodeParameters->codeChallenge) !== 1
            || strlen($authorizationCodeParameters->state) > 2048) {
            throw new InvalidAuthorizationRequestException();
        }

        return $client->name;
    }
}
