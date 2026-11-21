<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Query;

readonly class PrincipalReadModel
{
    /** @param array<int, array{policyIdentifier: string, name: string, statements: array<int, array{effect: string, actions: array<int, string>, resourceTypes: array<int, string>, condition: array{clauses: list<array{field: string, operator: string, value: string|list<string>}>}|null}>}> $policies */
    public function __construct(
        public string $principalIdentifier,
        public string $identityIdentifier,
        public string $status,
        public array $policies,
        public int $accountPrincipalCount,
    ) {
    }

    /** @return array{principalIdentifier: string, identityIdentifier: string, status: string, accountPrincipalCount: int, policies: array<int, array{policyIdentifier: string, name: string, statements: array<int, array{effect: string, actions: array<int, string>, resourceTypes: array<int, string>, condition: array{clauses: list<array{field: string, operator: string, value: string|list<string>}>}|null}>}>} */
    public function toArray(): array
    {
        return [
            'principalIdentifier' => $this->principalIdentifier,
            'identityIdentifier' => $this->identityIdentifier,
            'status' => $this->status,
            'accountPrincipalCount' => $this->accountPrincipalCount,
            'policies' => $this->policies,
        ];
    }
}
