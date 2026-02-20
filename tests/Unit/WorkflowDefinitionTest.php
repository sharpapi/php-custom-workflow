<?php

declare(strict_types=1);

use SharpAPI\CustomWorkflow\DTO\WorkflowDefinition;
use SharpAPI\CustomWorkflow\DTO\WorkflowParam;
use SharpAPI\CustomWorkflow\Enums\InputMode;
use SharpAPI\CustomWorkflow\Enums\ParamType;

it('creates a workflow definition from API response array', function () {
    $data = [
        'slug' => 'sentiment-analysis',
        'name' => 'Sentiment Analysis',
        'description' => 'Analyze text sentiment',
        'input_mode' => 'application/json',
        'output_schema' => ['sentiment' => 'string', 'confidence' => 'number'],
        'is_active' => true,
        'endpoint' => '/api/v1/custom/sentiment-analysis',
        'created_at' => '2026-01-15T10:00:00+00:00',
        'updated_at' => '2026-01-15T10:00:00+00:00',
        'params' => [
            'data' => [
                [
                    'key' => 'text',
                    'label' => 'Text to analyze',
                    'type' => 'json_string',
                    'required' => true,
                    'default_value' => null,
                ],
                [
                    'key' => 'context',
                    'label' => 'Context',
                    'type' => 'json_string',
                    'required' => false,
                    'default_value' => 'general',
                ],
            ],
        ],
    ];

    $definition = WorkflowDefinition::fromArray($data);

    expect($definition->slug)->toBe('sentiment-analysis');
    expect($definition->name)->toBe('Sentiment Analysis');
    expect($definition->inputMode)->toBe(InputMode::JSON);
    expect($definition->isActive)->toBeTrue();
    expect($definition->params)->toHaveCount(2);
    expect($definition->params[0])->toBeInstanceOf(WorkflowParam::class);
    expect($definition->params[0]->key)->toBe('text');
    expect($definition->params[0]->type)->toBe(ParamType::JSON_STRING);
    expect($definition->params[0]->required)->toBeTrue();
    expect($definition->params[1]->defaultValue)->toBe('general');
});

it('filters required and optional params', function () {
    $definition = WorkflowDefinition::fromArray([
        'slug' => 'test',
        'name' => 'Test',
        'input_mode' => 'application/json',
        'is_active' => true,
        'params' => [
            'data' => [
                ['key' => 'a', 'type' => 'json_string', 'required' => true],
                ['key' => 'b', 'type' => 'json_string', 'required' => false],
                ['key' => 'c', 'type' => 'json_number', 'required' => true],
            ],
        ],
    ]);

    expect($definition->requiredParams())->toHaveCount(2);
    expect($definition->optionalParams())->toHaveCount(1);
    expect($definition->optionalParams()[0]->key)->toBe('b');
});

it('converts back to array', function () {
    $definition = WorkflowDefinition::fromArray([
        'slug' => 'test',
        'name' => 'Test',
        'description' => null,
        'input_mode' => 'multipart/form-data',
        'output_schema' => null,
        'is_active' => true,
        'endpoint' => '/api/v1/custom/test',
        'params' => ['data' => []],
    ]);

    $array = $definition->toArray();

    expect($array['slug'])->toBe('test');
    expect($array['input_mode'])->toBe('multipart/form-data');
    expect($array['params'])->toBeArray()->toBeEmpty();
});
