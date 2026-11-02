<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\UseCase\Query;

readonly class PrincipalReadModel
{
    /** @param array<int, array{policyIdentifier: string, name: string, statements: array<int, array{effect: string, actions: array<int, string>, resourceTypes: array<int, string>}>}> $policies */
    public function __construct(
        public string $principalIdentifier,
        public string $identityIdentifier,
        public string $status,
        public array $policies,
    ) {
    }

    /** @return array{principalIdentifier: string, identityIdentifier: string, status: string, policies: array<int, array{policyIdentifier: string, name: string, statements: array<int, array{effect: string, actions: array<int, string>, resourceTypes: array<int, string>}>}>} */
    public function toArray(): array
    {
        return [
            'principalIdentifier' => $this->principalIdentifier,
            'identityIdentifier' => $this->identityIdentifier,
            'status' => $this->status,
            'policies' => $this->policies,
        ];
    }
}
