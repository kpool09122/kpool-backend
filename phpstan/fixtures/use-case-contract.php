<?php

namespace Source\RuleFixture\Application\UseCase\Query {
    class ExampleReadModel {}
}
namespace Source\RuleFixture\Application\UseCase\Command\Example {
    use Source\RuleFixture\Application\UseCase\Query\ExampleReadModel;
    use Source\Shared\Domain\ValueObject\AccountIdentifier;
    interface ExampleInputPort {}
    interface ExampleOutputPort {}
    class ExampleInput implements ExampleInputPort {}
    class ExampleOutput implements ExampleOutputPort {}
    interface ExampleInterface {
        public function process(ExampleInputPort $input, ExampleOutputPort $output): void;
    }
    class Example implements ExampleInterface {
        public function __construct(AccountIdentifier $account) {}
        public function process(ExampleInputPort $input, ExampleOutputPort $output): void {}
        public function extra(): void {}
        private function helper(): void {}
    }
    class Helper {
        public function extra(): string { return ''; }
    }
}
namespace Source\RuleFixture\Application\UseCase\Command\Invalid {
    use Source\Shared\Domain\ValueObject\AccountIdentifier;
    class Invalid {
        public function process(AccountIdentifier $account, string $value): string { return ''; }
    }
}
namespace Source\RuleFixture\Application\UseCase\Query\Listing {
    use Source\RuleFixture\Application\UseCase\Query\ExampleReadModel;
    use Source\RuleFixture\Application\UseCase\Command\Example\ExampleInput;
    interface ListingInterface {
        /** @return list<ExampleReadModel> */
        public function process(ExampleInput $input): array;
    }
}
namespace Source\RuleFixture\Infrastructure\Query {
    use Source\RuleFixture\Application\UseCase\Query\Listing\ListingInterface;
    use Source\RuleFixture\Application\UseCase\Query\ExampleReadModel;
    use Source\RuleFixture\Application\UseCase\Command\Example\ExampleInput;
    class Listing implements ListingInterface {
        /** @return list<ExampleReadModel> */
        public function process(ExampleInput $input): array { return []; }
        public function extra(): void {}
    }
}
namespace Source\RuleFixture\Application\UseCase\Query\Nullable {
    use Source\RuleFixture\Application\UseCase\Query\ExampleReadModel;
    class Nullable {
        public function process(): ?ExampleReadModel { return null; }
    }
}
namespace Source\RuleFixture\Application\UseCase\Query\UntypedArray {
    class UntypedArray {
        public function process(): array { return []; }
    }
}
namespace Source\RuleFixture\Application\UseCase\Query\Single {
    use Source\RuleFixture\Application\UseCase\Query\ExampleReadModel;
    class Single {
        public function process(): ExampleReadModel { return new ExampleReadModel(); }
    }
}
