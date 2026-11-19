<?php

declare(strict_types=1);

namespace Application\Mcp;

use LogicException;
use Opis\JsonSchema\Validator;
use stdClass;

final readonly class OpenApiTool
{
    /** @param array<string, mixed> $inputSchema
     * @param array<string, string> $argumentLocations
     * @param list<string> $scopes
     * @param array<string, bool> $annotations
     */
    public function __construct(
        public string $name,
        public string $method,
        public string $path,
        private string $description,
        private array $inputSchema,
        private array $argumentLocations,
        public array $scopes,
        private array $annotations,
        public ?string $resultProjector = null,
        private bool $hasRequestBody = false,
        private bool $requestBodyRequired = true,
    ) {
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $schemes = [['type' => 'oauth2', 'scopes' => $this->scopes]];

        return ['name' => $this->name, 'description' => $this->description, 'inputSchema' => $this->inputSchema,
            'securitySchemes' => $schemes, '_meta' => ['securitySchemes' => $schemes], 'annotations' => (object) $this->annotations];
    }

    public function accepts(stdClass $arguments): bool
    {
        foreach ($this->argumentLocations as $name => $location) {
            if ($location === 'path' && in_array($arguments->{$name} ?? null, ['.', '..'], true)) {
                return false;
            }
        }
        $schema = json_decode(json_encode($this->inputSchema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        if (! $schema instanceof stdClass) {
            throw new LogicException('Invalid MCP input schema.');
        }

        return (new Validator())->validate($arguments, $schema)->isValid();
    }

    /** @return array{path: string, body: array<string, mixed>|null, query: array<string, mixed>} */
    public function request(stdClass $arguments): array
    {
        $path = $this->path;
        $body = $query = [];
        foreach (get_object_vars($arguments) as $name => $value) {
            $location = $this->argumentLocations[$name] ?? throw new LogicException('Unknown MCP argument.');
            if ($location === 'path') {
                if (! is_string($value) && ! is_int($value)) {
                    throw new LogicException('Invalid path argument.');
                }
                $path = str_replace('{' . $name . '}', rawurlencode((string) $value), $path);
            } elseif ($location === 'query_csv') {
                if (! is_array($value)) {
                    throw new LogicException('Invalid query array.');
                }
                $query[$name] = implode(',', array_map(OpenApiDocument::string(...), $value));
            } elseif ($location === 'query') {
                $query[$name] = is_bool($value) ? ($value ? 'true' : 'false') : ($value ?? '');
            } else {
                $body[$name] = $value;
            }
        }

        return ['path' => $path, 'body' => $this->hasRequestBody && ($this->requestBodyRequired || $body !== []) ? OpenApiDocument::map($body) : null, 'query' => OpenApiDocument::map($query)];
    }
}
