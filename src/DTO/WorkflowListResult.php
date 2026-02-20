<?php

declare(strict_types=1);

namespace SharpAPI\CustomWorkflow\DTO;

class WorkflowListResult
{
    /**
     * @param list<WorkflowDefinition> $workflows
     */
    public function __construct(
        public readonly array $workflows,
        public readonly ?int $total = null,
        public readonly ?int $perPage = null,
        public readonly ?int $currentPage = null,
        public readonly ?int $totalPages = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $workflows = [];
        foreach ($data['data'] ?? [] as $item) {
            $workflows[] = WorkflowDefinition::fromArray($item);
        }

        $pagination = $data['meta']['pagination'] ?? [];

        return new self(
            workflows: $workflows,
            total: $pagination['total'] ?? null,
            perPage: $pagination['per_page'] ?? null,
            currentPage: $pagination['current_page'] ?? null,
            totalPages: $pagination['total_pages'] ?? null,
        );
    }

    public function count(): int
    {
        return count($this->workflows);
    }

    public function isEmpty(): bool
    {
        return empty($this->workflows);
    }
}
