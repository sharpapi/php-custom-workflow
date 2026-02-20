<?php

declare(strict_types=1);

use SharpAPI\CustomWorkflow\DTO\WorkflowDefinition;
use SharpAPI\CustomWorkflow\Exceptions\ValidationException;
use SharpAPI\CustomWorkflow\Validation\PayloadValidator;

function buildJsonWorkflow(array $params): WorkflowDefinition
{
    return WorkflowDefinition::fromArray([
        'slug' => 'test',
        'name' => 'Test',
        'input_mode' => 'application/json',
        'is_active' => true,
        'params' => ['data' => $params],
    ]);
}

function buildFormDataWorkflow(array $params): WorkflowDefinition
{
    return WorkflowDefinition::fromArray([
        'slug' => 'test',
        'name' => 'Test',
        'input_mode' => 'multipart/form-data',
        'is_active' => true,
        'params' => ['data' => $params],
    ]);
}

// -- JSON mode: required fields --

it('passes when all required JSON fields are present', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'text', 'type' => 'json_string', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['text' => 'hello']);
    expect(true)->toBeTrue(); // no exception thrown
});

it('fails when a required JSON field is missing', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'text', 'type' => 'json_string', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, []);
})->throws(ValidationException::class);

// -- JSON mode: type checks --

it('validates json_string type', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'text', 'type' => 'json_string', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['text' => 123]);
})->throws(ValidationException::class);

it('validates json_number type', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'count', 'type' => 'json_number', 'required' => true],
    ]);

    // Valid int
    PayloadValidator::validate($workflow, ['count' => 42]);
    // Valid float
    PayloadValidator::validate($workflow, ['count' => 3.14]);
    expect(true)->toBeTrue();
});

it('rejects string for json_number', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'count', 'type' => 'json_number', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['count' => '42']);
})->throws(ValidationException::class);

it('validates json_boolean type', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'active', 'type' => 'json_boolean', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['active' => true]);
    expect(true)->toBeTrue();
});

it('rejects string for json_boolean', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'active', 'type' => 'json_boolean', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['active' => 'true']);
})->throws(ValidationException::class);

it('validates json_object type', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'meta', 'type' => 'json_object', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['meta' => ['key' => 'value']]);
    expect(true)->toBeTrue();
});

it('rejects list for json_object', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'meta', 'type' => 'json_object', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['meta' => ['a', 'b']]);
})->throws(ValidationException::class);

it('validates json_array type', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'items', 'type' => 'json_array', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['items' => ['a', 'b']]);
    expect(true)->toBeTrue();
});

it('rejects object for json_array', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'items', 'type' => 'json_array', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['items' => ['key' => 'value']]);
})->throws(ValidationException::class);

// -- JSON mode: extra params --

it('rejects extra unknown JSON parameters', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'text', 'type' => 'json_string', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['text' => 'hello', 'unknown' => 'bad']);
})->throws(ValidationException::class);

it('exposes errors on validation exception', function () {
    $workflow = buildJsonWorkflow([
        ['key' => 'text', 'type' => 'json_string', 'required' => true],
    ]);

    try {
        PayloadValidator::validate($workflow, ['text' => 'hello', 'extra' => 'bad']);
    } catch (ValidationException $e) {
        expect($e->getErrors())->toHaveKey('extra');
        expect($e->getErrors()['extra'])->toContain('Unknown parameter');
    }
});

// -- Form-data mode --

it('passes form-data validation with required text field', function () {
    $workflow = buildFormDataWorkflow([
        ['key' => 'description', 'type' => 'form_data_text', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, ['description' => 'A document']);
    expect(true)->toBeTrue();
});

it('fails form-data when required text field is missing', function () {
    $workflow = buildFormDataWorkflow([
        ['key' => 'description', 'type' => 'form_data_text', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, []);
})->throws(ValidationException::class);

it('fails form-data when required file is missing', function () {
    $workflow = buildFormDataWorkflow([
        ['key' => 'document', 'type' => 'form_data_file', 'required' => true],
    ]);

    PayloadValidator::validate($workflow, [], []);
})->throws(ValidationException::class);

it('passes form-data when file is present and readable', function () {
    $workflow = buildFormDataWorkflow([
        ['key' => 'document', 'type' => 'form_data_file', 'required' => true],
    ]);

    // Use a real readable file
    PayloadValidator::validate($workflow, [], ['document' => __FILE__]);
    expect(true)->toBeTrue();
});
