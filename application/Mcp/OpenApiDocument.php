<?php

declare(strict_types=1);

namespace Application\Mcp;

use LogicException;
use stdClass;
use Symfony\Component\Yaml\Yaml;

/** Reads only local, trusted OpenAPI documents; never follows remote references. */
final readonly class OpenApiDocument
{
    /** @param array<string, mixed> $document */
    public function __construct(private array $document)
    {
        if (($document['openapi'] ?? null) !== '3.0.0') {
            throw new LogicException('MCP generation requires OpenAPI 3.0.0.');
        }
    }

    public static function fromFile(string $file): self
    {
        return new self(self::map(Yaml::parseFile($file)));
    }

    /** @return array<string, mixed> */
    public function operation(string $path, string $method): array
    {
        $paths = self::map($this->document['paths'] ?? null);
        $item = $this->resolve(self::map($paths[$path] ?? null));
        if (isset($item['servers'])) {
            throw new LogicException('Path server overrides are not supported.');
        }
        $operation = self::map($item[strtolower($method)] ?? null);
        $operation['parameters'] = array_merge(self::items($item['parameters'] ?? []), self::items($operation['parameters'] ?? []));

        return $operation;
    }

    public function prefix(): string
    {
        $servers = self::items($this->document['servers'] ?? null);
        $server = self::map($servers[0] ?? null);
        $url = self::string($server['url'] ?? null);
        if (! preg_match('~\A/api/v1/[a-z-]+\z~D', $url)) {
            throw new LogicException('MCP API server must be a fixed local API prefix.');
        }

        return $url;
    }

    /** @param array<string, mixed> $value
     * @param list<string> $seen
     * @return array<string, mixed>
     */
    public function resolve(array $value, array $seen = []): array
    {
        if (! isset($value['$ref'])) {
            return $value;
        }
        $ref = self::string($value['$ref']);
        if (! str_starts_with($ref, '#/components/') || in_array($ref, $seen, true)) {
            throw new LogicException('Unsupported or cyclic OpenAPI reference.');
        }
        $target = $this->document;
        foreach (explode('/', substr($ref, 2)) as $part) {
            $target = self::map($target[str_replace(['~1', '~0'], ['/', '~'], $part)] ?? null);
        }

        return $this->resolve($target, [...$seen, $ref]);
    }

    /** Convert OpenAPI 3.0 schemas to self-contained JSON Schema input definitions.
     * @param array<string, mixed> $schema
     * @param list<string> $seen
     * @return array<string, mixed>|stdClass
     */
    public function schema(array $schema, array $seen = []): array|stdClass
    {
        if (isset($schema['$ref'])) {
            $ref = self::string($schema['$ref']);
            if (in_array($ref, $seen, true)) {
                throw new LogicException('Recursive MCP input schemas are not supported.');
            }

            return $this->schema($this->resolve($schema), [...$seen, $ref]);
        }
        $allowed = ['type', 'format', 'description', 'title', 'default', 'example', 'deprecated', 'nullable', 'readOnly', 'writeOnly', 'properties', 'required', 'additionalProperties', 'items', 'allOf', 'anyOf', 'oneOf', 'not', 'enum', 'minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'multipleOf', 'minLength', 'maxLength', 'pattern', 'minItems', 'maxItems', 'uniqueItems', 'minProperties', 'maxProperties'];
        if (array_diff(array_keys($schema), $allowed) !== []) {
            throw new LogicException('Unsupported OpenAPI schema keyword.');
        }
        $nullable = ($schema['nullable'] ?? false) === true;
        unset($schema['nullable'], $schema['example'], $schema['readOnly'], $schema['writeOnly']);
        foreach (['allOf', 'anyOf', 'oneOf'] as $keyword) {
            if (isset($schema[$keyword])) {
                $schema[$keyword] = array_map(fn (mixed $part): array|stdClass => $this->schema(self::map($part), $seen), self::items($schema[$keyword]));
            }
        }
        foreach (['items', 'not'] as $keyword) {
            if (isset($schema[$keyword])) {
                $schema[$keyword] = $this->schema(self::map($schema[$keyword]), $seen);
            }
        }
        if (is_array($schema['additionalProperties'] ?? null)) {
            $schema['additionalProperties'] = $this->schema(self::map($schema['additionalProperties']), $seen);
        }
        if (isset($schema['properties']) || ($schema['type'] ?? null) === 'object') {
            $properties = [];
            $required = array_map(self::string(...), self::items($schema['required'] ?? []));
            foreach (self::map($schema['properties'] ?? []) as $name => $property) {
                $property = self::map($property);
                if (($this->resolve($property)['readOnly'] ?? false) === true) {
                    $required = array_values(array_diff($required, [$name]));

                    continue;
                }
                $properties[$name] = $this->schema($property, $seen);
            }
            $schema['properties'] = $properties === [] ? new stdClass() : $properties;
            $schema['required'] = $required;
        }
        foreach (['Minimum' => 'minimum', 'Maximum' => 'maximum'] as $suffix => $bound) {
            $exclusive = 'exclusive' . $suffix;
            if (isset($schema[$exclusive])) {
                if ($schema[$exclusive] === true) {
                    if (! isset($schema[$bound])) {
                        throw new LogicException('Exclusive bound requires a value.');
                    }
                    $schema[$exclusive] = $schema[$bound];
                    unset($schema[$bound]);
                } else {
                    unset($schema[$exclusive]);
                }
            }
        }

        $result = $schema === [] ? new stdClass() : $schema;

        return $nullable ? ['anyOf' => [$result, ['type' => 'null']]] : $result;
    }

    /** @return array<string, mixed> */
    public static function map(mixed $value): array
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            throw new LogicException('Expected an OpenAPI object.');
        }
        $result = [];
        foreach ($value as $key => $item) {
            if (! is_string($key)) {
                throw new LogicException('Expected string OpenAPI keys.');
            }
            $result[$key] = $item;
        }

        return $result;
    }

    /** @return list<mixed> */
    public static function items(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new LogicException('Expected an OpenAPI list.');
        }

        return $value;
    }

    public static function string(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            throw new LogicException('Expected a non-empty OpenAPI string.');
        }

        return $value;
    }
}
