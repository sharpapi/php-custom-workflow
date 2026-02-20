<?php

declare(strict_types=1);

namespace SharpAPI\CustomWorkflow\DTO;

use SharpAPI\CustomWorkflow\Enums\ParamType;

class WorkflowParam
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly ParamType $type,
        public readonly bool $required,
        public readonly ?string $defaultValue = null,
    ) {}

    public static function fromArray(array $data): self
    {
        // Unwrap JSON API envelope if present
        $attrs = $data['attributes'] ?? $data;

        return new self(
            key: $attrs['key'],
            label: $attrs['label'] ?? $attrs['key'],
            type: ParamType::from($attrs['type']),
            required: (bool) ($attrs['required'] ?? false),
            defaultValue: $attrs['default_value'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type->value,
            'required' => $this->required,
            'default_value' => $this->defaultValue,
        ];
    }
}
