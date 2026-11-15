<?php

declare(strict_types=1);

namespace Tests\Helper;

use Application\Models\Account\Principal;
use Application\Models\Identity\AuthorizationConsent;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\PendingAuthorization;
use Source\Identity\Domain\Entity\AuthorizationConsent as Consent;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class AuthorizationRequestScenario
{
    public function __construct(public Consent $base, public AuthorizationRequestStorageServiceInterface $authorizationRequestStorageService, public PendingAuthorization $pending, public Request $request)
    {
    }

    public static function create(): self
    {
        $base = CreateAuthorizationConsent::create();
        AuthorizationConsent::query()->whereKey((string) $base->identifier())->delete();
        CreateAccount::create((string) $base->originalAccountIdentifier());
        Principal::query()->create(['id' => (string) $base->originalPrincipalIdentifier(), 'identity_id' => (string) $base->identityIdentifier(), 'account_id' => (string) $base->originalAccountIdentifier(), 'status' => 'active']);
        $request = Request::create('/');
        $session = new Store('consent-test', new ArraySessionHandler(30));
        $session->start();
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        $storage = app(AuthorizationRequestStorageServiceInterface::class);
        $parameters = new AuthorizationCodeParameters($base->clientIdentifier(), CreateAuthorizationConsent::REDIRECT, AuthorizationScope::cases(), CreateAuthorizationConsent::RESOURCE, rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 64), true)), '+/', '-_'), '='), ' original state & value ');

        return new self($base, $storage, $storage->store($parameters, $base->identityIdentifier()), $request);
    }
}
