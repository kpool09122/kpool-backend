<?php

declare(strict_types=1);

namespace Application\Mcp;

use LogicException;
use stdClass;

final class OpenApiToolRegistry
{
    /** @var array<string, OpenApiTool> */
    private array $tools = [];

    /** @param array<string, array<string, mixed>> $registrations */
    public function __construct(array $registrations)
    {
        $documents = [];
        foreach ($registrations as $name => $registration) {
            $file = OpenApiDocument::string($registration['document'] ?? null);
            $document = $documents[$file] ??= OpenApiDocument::fromFile($file);
            $this->tools[$name] = $this->compile($name, $registration, $document);
        }
    }

    /** @return list<array<string, mixed>> */
    public function definitions(): array
    {
        return array_values(array_map(static fn (OpenApiTool $tool): array => $tool->definition(), $this->tools));
    }

    public function find(string $name): ?OpenApiTool
    {
        return $this->tools[$name] ?? null;
    }

    /** Require a full replacement object while deriving its fields from OpenAPI.
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function completeObject(array $schema): array
    {
        if (isset($schema['allOf'])) {
            $schema['allOf'] = array_map(fn (mixed $part): array => $this->completeObject(OpenApiDocument::map($part)), OpenApiDocument::items($schema['allOf']));

            return $schema;
        }
        if (isset($schema['anyOf'])) {
            $schema['anyOf'] = array_map(fn (mixed $part): array => $this->completeObject(OpenApiDocument::map($part)), OpenApiDocument::items($schema['anyOf']));

            return $schema;
        }
        if (($schema['type'] ?? null) !== 'object' || ! isset($schema['properties'])) {
            throw new LogicException('Complete argument must be an explicit object.');
        }
        $schema['required'] = array_keys(OpenApiDocument::map($schema['properties']));
        $schema['additionalProperties'] = false;

        return $schema;
    }

    /** Extract argument names without discarding the original allOf validation.
     * @param array<string, mixed> $schema
     * @return array{properties: array<string, mixed>, required: list<mixed>}
     */
    private function bodyShape(array $schema): array
    {
        if (array_diff(array_keys($schema), ['type', 'properties', 'required', 'description', 'title', 'allOf', 'additionalProperties']) !== []
            || (isset($schema['type']) && $schema['type'] !== 'object')
            || (isset($schema['additionalProperties']) && $schema['additionalProperties'] !== true)
            || (! isset($schema['type']) && ! isset($schema['allOf']))) {
            throw new LogicException('MCP request body must have explicit object properties.');
        }
        $properties = OpenApiDocument::map(($schema['properties'] ?? []) instanceof stdClass ? [] : ($schema['properties'] ?? []));
        $required = OpenApiDocument::items($schema['required'] ?? []);
        foreach (OpenApiDocument::items($schema['allOf'] ?? []) as $part) {
            $shape = $this->bodyShape(OpenApiDocument::map($part));
            foreach ($shape['properties'] as $key => $property) {
                $properties[$key] = isset($properties[$key]) ? ['allOf' => [$properties[$key], $property]] : $property;
            }
            $required = array_merge($required, $shape['required']);
        }

        return ['properties' => $properties, 'required' => $required];
    }

    /** @param array<string, mixed> $registration */
    private function compile(string $name, array $registration, OpenApiDocument $document): OpenApiTool
    {
        $method = strtoupper(OpenApiDocument::string($registration['method'] ?? null));
        $path = OpenApiDocument::string($registration['path'] ?? null);
        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)
            || ! preg_match('~\A/[a-zA-Z0-9_/{}/-]+\z~D', $path)
            || ! preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/D', $name)) {
            throw new LogicException('Invalid MCP endpoint registration.');
        }
        $operation = $document->operation($path, $method);
        if (isset($operation['servers'])) {
            throw new LogicException('Operation server overrides are not supported.');
        }
        $internalBearer = false;
        foreach (OpenApiDocument::items($operation['security'] ?? []) as $alternative) {
            $alternative = OpenApiDocument::map($alternative);
            if (array_keys($alternative) === ['InternalApiBearerAuth'] && $alternative['InternalApiBearerAuth'] === []) {
                $internalBearer = true;
            }
        }
        if (! $internalBearer) {
            throw new LogicException('MCP endpoints must support internal API Bearer authentication.');
        }
        $properties = $locations = $required = [];
        $managedHeaders = array_map(OpenApiDocument::string(...), OpenApiDocument::items($registration['managedHeaders'] ?? []));
        $handledHeaders = [];
        foreach (OpenApiDocument::items($operation['parameters']) as $parameter) {
            $parameter = $document->resolve(OpenApiDocument::map($parameter));
            $key = OpenApiDocument::string($parameter['name'] ?? null);
            $location = OpenApiDocument::string($parameter['in'] ?? null);
            if ($location === 'header' && in_array($key, $managedHeaders, true)) {
                // Optional visitor metadata has no trusted MCP source. Omit it; never accept AI signatures.
                if (! in_array($key, ['X-Kpool-Visitor-Country', 'X-Kpool-Visitor-Region', 'X-Kpool-Visitor-Timestamp', 'X-Kpool-Visitor-Signature'], true)
                    || ($parameter['required'] ?? false) === true) {
                    throw new LogicException('Unsupported managed MCP header.');
                }
                $handledHeaders[] = $key;

                continue;
            }
            if (! in_array($location, ['path', 'query'], true) || isset($properties[$key])
                || isset($parameter['content']) || ($parameter['allowReserved'] ?? false) !== false
                || ($parameter['style'] ?? ($location === 'path' ? 'simple' : 'form')) !== ($location === 'path' ? 'simple' : 'form')) {
                throw new LogicException('Unsupported or ambiguous MCP API parameter.');
            }
            $parameterSchema = $document->resolve(OpenApiDocument::map($parameter['schema'] ?? null));
            $schema = OpenApiDocument::map($document->schema($parameterSchema));
            $arrayQuery = $location === 'query' && ($parameterSchema['type'] ?? null) === 'array'
                && ($parameter['explode'] ?? true) === false
                && ($document->resolve(OpenApiDocument::map($parameterSchema['items'] ?? null))['type'] ?? null) === 'string';
            if (! $arrayQuery && ! in_array($parameterSchema['type'] ?? null, ['string', 'integer', 'number', 'boolean'], true)) {
                throw new LogicException('Only scalar parameters and non-exploded string query arrays are supported.');
            }
            if ($location === 'path' && ((! in_array($parameterSchema['type'], ['string', 'integer'], true)) || ($parameterSchema['nullable'] ?? false) === true)) {
                throw new LogicException('Path parameters must be strings or integers.');
            }
            if ($location === 'path' && (! str_contains($path, '{' . $key . '}') || ($parameter['required'] ?? false) !== true)) {
                throw new LogicException('Path parameter must be present and required.');
            }
            if (isset($parameter['description'])) {
                $schema['description'] = OpenApiDocument::string($parameter['description']);
            }
            $properties[$key] = $schema;
            $locations[$key] = $arrayQuery ? 'query_csv' : $location;
            if (($parameter['required'] ?? false) === true) {
                $required[] = $key;
            }
        }
        if (array_diff($managedHeaders, $handledHeaders) !== []) {
            throw new LogicException('Managed header is absent from OpenAPI.');
        }
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        foreach ($matches[1] as $key) {
            if (($locations[$key] ?? null) !== 'path') {
                throw new LogicException('Missing OpenAPI path parameter.');
            }
        }
        $bodyConstraints = [];
        if (isset($operation['requestBody'])) {
            $body = $document->resolve(OpenApiDocument::map($operation['requestBody']));
            $content = OpenApiDocument::map($body['content'] ?? null);
            if (array_keys($content) !== ['application/json']) {
                throw new LogicException('Only JSON object request bodies are supported.');
            }
            $media = OpenApiDocument::map($content['application/json']);
            $bodySchema = OpenApiDocument::map($document->schema(OpenApiDocument::map($media['schema'] ?? null)));
            $schema = $this->bodyShape($bodySchema);
            $bodyRequired = ($body['required'] ?? false) === true;
            $bodyConstraints = ['allOf' => [$bodySchema]];
            if (! $bodyRequired && $schema['properties'] !== []) {
                $bodyConstraints = [
                    'if' => ['anyOf' => array_map(static fn (string $key): array => ['required' => [$key]], array_keys($schema['properties']))],
                    'then' => $bodySchema,
                ];
            }
            foreach (OpenApiDocument::map($schema['properties'] instanceof stdClass ? [] : $schema['properties']) as $key => $property) {
                if (isset($properties[$key])) {
                    throw new LogicException('MCP parameter/body names must not collide.');
                }
                $properties[$key] = $property;
                $locations[$key] = 'body';
            }
            foreach ($bodyRequired ? OpenApiDocument::items($schema['required'] ?? []) : [] as $key) {
                $required[] = OpenApiDocument::string($key);
            }
        }
        foreach (OpenApiDocument::items($registration['requireCompleteObjects'] ?? []) as $key) {
            $key = OpenApiDocument::string($key);
            if (! isset($properties[$key])) {
                throw new LogicException('Complete object argument is absent from OpenAPI.');
            }
            $properties[$key] = $this->completeObject(OpenApiDocument::map($properties[$key]));
        }
        // Correlate union object variants with an existing discriminator argument.
        foreach (OpenApiDocument::map($registration['objectVariants'] ?? []) as $key => $variantRegistration) {
            $variantRegistration = OpenApiDocument::map($variantRegistration);
            $discriminator = OpenApiDocument::string($variantRegistration['discriminator'] ?? null);
            if (! isset($properties[$key], $properties[$discriminator])) {
                throw new LogicException('Object variant arguments are absent from OpenAPI.');
            }
            foreach (OpenApiDocument::map($variantRegistration['schemas'] ?? null) as $value => $schemaName) {
                $variant = OpenApiDocument::map($document->schema(['$ref' => '#/components/schemas/' . OpenApiDocument::string($schemaName)]));
                if (in_array($key, OpenApiDocument::items($registration['requireCompleteObjects'] ?? []), true)) {
                    $variant = $this->completeObject($variant);
                }
                $bodyConstraints['allOf'][] = [
                    'if' => ['properties' => [$discriminator => ['const' => $value]], 'required' => [$discriminator]],
                    'then' => ['properties' => [$key => $variant]],
                ];
            }
        }
        // MCP may require explicit selection even when omission is allowed by the HTTP API.
        foreach (OpenApiDocument::items($registration['requireExplicitArguments'] ?? []) as $key) {
            $key = OpenApiDocument::string($key);
            if (! isset($properties[$key])) {
                throw new LogicException('MCP required argument is absent from OpenAPI.');
            }
            $required[] = $key;
        }
        $scopes = array_map(OpenApiDocument::string(...), OpenApiDocument::items($registration['scopes'] ?? null));
        $annotations = OpenApiDocument::map($registration['annotations'] ?? []);
        foreach ($annotations as $value) {
            if (! is_bool($value)) {
                throw new LogicException('Invalid MCP annotation.');
            }
        }
        $description = OpenApiDocument::string($operation['description'] ?? $operation['summary'] ?? null);
        if (isset($registration['instructions'])) {
            $description .= "\n\n" . OpenApiDocument::string($registration['instructions']);
        }
        $projector = isset($registration['resultProjector']) ? OpenApiDocument::string($registration['resultProjector']) : null;
        if (! in_array($projector, [null, 'account_context', 'account_switched'], true)) {
            throw new LogicException('Unknown MCP result projector.');
        }

        return new OpenApiTool(
            $name,
            $method,
            $document->prefix() . $path,
            $description,
            ['type' => 'object', 'properties' => $properties === [] ? new stdClass() : $properties, 'required' => array_values(array_unique($required)), 'additionalProperties' => false] + $bodyConstraints,
            $locations,
            $scopes,
            $annotations,
            $projector,
            isset($operation['requestBody']),
            $bodyRequired ?? false,
        );
    }
}
