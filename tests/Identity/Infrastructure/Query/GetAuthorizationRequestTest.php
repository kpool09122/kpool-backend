<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use Application\Models\Account\Delegation;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInterface;
use Tests\Helper\AuthorizationRequestScenario;
use Tests\Helper\CreateAccount;
use Tests\TestCase;

#[Group('useDb')]
class GetAuthorizationRequestTest extends TestCase
{
    public function testReturnsOriginalAccountAndListsActiveApprovedDelegationsInIdentifierOrder(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        $originalAccountIdentifier = (string) $scenario->base->originalAccountIdentifier();
        $delegationIdentifiers = [(string) Str::uuid7(), (string) Str::uuid7()];
        sort($delegationIdentifiers, SORT_STRING);
        $second = $this->createDelegation($originalAccountIdentifier, $delegationIdentifiers[1], 'Second');
        $first = $this->createDelegation($originalAccountIdentifier, $delegationIdentifiers[0], 'First');
        $this->createDelegation($originalAccountIdentifier, (string) Str::uuid7(), 'Pending', status: 'pending');
        $this->createDelegation($originalAccountIdentifier, (string) Str::uuid7(), 'Suspended', accountStatus: 'suspended');
        $denied = $this->createDelegation($originalAccountIdentifier, (string) Str::uuid7(), 'Denied');
        $policyEvaluator = $this->createStub(PolicyEvaluatorInterface::class);
        $policyEvaluator->method('evaluate')->willReturnCallback(
            static fn (Principal $principal, Action $action, Resource $resource): bool => (string) $resource->delegationIdentifier() !== $denied->id,
        );
        app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $query = app(GetAuthorizationRequestInterface::class);
        $identity = $scenario->base->identityIdentifier();
        $input = new GetAuthorizationRequestInput($scenario->pending->identifier, $identity);
        $sessionBeforeRead = $scenario->request->session()->all();
        $result = $query->process($input)->toArray();
        $this->assertSame($sessionBeforeRead, $scenario->request->session()->all());
        $this->assertNull(app(CurrentAccountServiceInterface::class)->find($identity));
        $original = ['accountIdentifier' => $originalAccountIdentifier, 'name' => 'Test Account', 'delegationIdentifier' => null];
        $firstChoice = ['accountIdentifier' => $first->delegator_account_id, 'name' => 'First', 'delegationIdentifier' => $first->id];
        $secondChoice = ['accountIdentifier' => $second->delegator_account_id, 'name' => 'Second', 'delegationIdentifier' => $second->id];
        $this->assertSame('ChatGPT', $result['clientName']);
        $this->assertContains(['scope' => 'wiki:read', 'description' => 'Wiki の閲覧'], $result['scopes']);
        $this->assertSame($originalAccountIdentifier, $result['originalAccountIdentifier']);
        $this->assertSame([$original, $firstChoice, $secondChoice], $result['accounts']);

        $first->update(['status' => 'rejected']);
        $this->assertSame([$original, $secondChoice], $query->process($input)->toArray()['accounts']);
    }

    private function createDelegation(string $delegateAccountIdentifier, string $identifier, string $accountName, string $status = 'approved', string $accountStatus = 'active'): Delegation
    {
        $delegatorAccountIdentifier = (string) Str::uuid7();
        CreateAccount::create($delegatorAccountIdentifier, ['name' => $accountName, 'status' => $accountStatus]);

        return Delegation::query()->create([
            'id' => $identifier,
            'affiliation_id' => (string) Str::uuid7(),
            'delegate_account_id' => $delegateAccountIdentifier,
            'delegator_account_id' => $delegatorAccountIdentifier,
            'requested_by_account_id' => $delegatorAccountIdentifier,
            'status' => $status,
            'direction' => DelegationDirection::FROM_AGENCY->value,
            'requested_at' => now(),
        ]);
    }
}
