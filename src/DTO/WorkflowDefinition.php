<?php

declare(strict_types=1);

namespace SharpAPI\CustomWorkflow\DTO;

use SharpAPI\CustomWorkflow\Enums\InputMode;
use SharpAPI\CustomWorkflow\Validation\PayloadValidator;

class WorkflowDefinition
{
    /**
     * @param list<WorkflowParam> $params
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly ?string $description,
        public readonly InputMode $inputMode,
        public readonly ?array $outputSchema,
        public readonly bool $isActive,
        public readonly string $endpoint,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly array $params = [],
    ) {}

    public static function fromArray(array $data): self
    {
        // Unwrap JSON API envelope: {"type":"...","id":"...","attributes":{...}}
        $attrs = $data['attributes'] ?? $data;

        $params = [];
        // Params may be nested under attributes or at top level
        $paramsRaw = $attrs['params'] ?? $data['params'] ?? [];
        $paramsData = $paramsRaw['data'] ?? $paramsRaw;
        foreach ($paramsData as $p) {
            $params[] = WorkflowParam::fromArray($p);
        }

        return new self(
            slug: $attrs['slug'],
            name: $attrs['name'],
            description: $attrs['description'] ?? null,
            inputMode: InputMode::from($attrs['input_mode']),
            outputSchema: $attrs['output_schema'] ?? null,
            isActive: (bool) ($attrs['is_active'] ?? true),
            endpoint: $attrs['endpoint'] ?? '/api/v1/custom/' . $attrs['slug'],
            createdAt: $attrs['created_at'] ?? null,
            updatedAt: $attrs['updated_at'] ?? null,
            params: $params,
        );
    }

    /**
     * @return list<WorkflowParam>
     */
    public function requiredParams(): array
    {
        return array_values(array_filter(
            $this->params,
            fn (WorkflowParam $p) => $p->required
        ));
    }

    /**
     * @return list<WorkflowParam>
     */
    public function optionalParams(): array
    {
        return array_values(array_filter(
            $this->params,
            fn (WorkflowParam $p) => !$p->required
        ));
    }

    /**
     * Validate a payload against this workflow's parameter definitions.
     *
     * @param array $params JSON key-value pairs
     * @param array $files  File paths keyed by param name (form-data mode)
     *
     * @throws \SharpAPI\CustomWorkflow\Exceptions\ValidationException
     */
    public function validate(array $params = [], array $files = []): void
    {
        PayloadValidator::validate($this, $params, $files);
    }

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'input_mode' => $this->inputMode->value,
            'output_schema' => $this->outputSchema,
            'is_active' => $this->isActive,
            'endpoint' => $this->endpoint,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'params' => array_map(fn (WorkflowParam $p) => $p->toArray(), $this->params),
        ];
    }
}
