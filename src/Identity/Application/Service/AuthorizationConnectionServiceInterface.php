<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;

/** Issues authorization codes and exchanges tokens for authorization connections. */
interface AuthorizationConnectionServiceInterface
{
    /**
     * @return string Validated client redirect URL containing the authorization code and state.
     * @throws InvalidAuthorizationRequestException
     */
    public function issueCode(AuthorizationConnectionIdentifier $connectionIdentifier, AuthorizationCodeParameters $authorizationCodeParameters): string;

    /**
     * The caller owns the transaction, including refresh rotation and old-token revocation.
     * @throws OAuthServerException
     */
    public function exchange(ServerRequestInterface $request): ResponseInterface;
}
