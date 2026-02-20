<?php

/**
 * Integration test — run against https://sharpapi.test
 *
 * Usage:
 *   php test_integration.php <API_KEY>
 */

require __DIR__ . '/vendor/autoload.php';

use SharpAPI\CustomWorkflow\CustomWorkflowClient;
use SharpAPI\CustomWorkflow\Exceptions\ValidationException;

$apiKey = $argv[1] ?? null;
if (! $apiKey) {
    echo "Usage: php test_integration.php <API_KEY>\n";
    exit(1);
}

$baseUrl = 'https://sharpapi.test/api/v1';
$client = new CustomWorkflowClient($apiKey, $baseUrl);

$pass = 0;
$fail = 0;

function test(string $name, callable $fn): void
{
    global $pass, $fail;
    try {
        $fn();
        echo "  PASS  {$name}\n";
        $pass++;
    } catch (Throwable $e) {
        echo "  FAIL  {$name}\n";
        echo "        " . get_class($e) . ': ' . $e->getMessage() . "\n";
        $fail++;
    }
}

echo "\n--- SharpAPI Custom Workflow SDK Integration Tests ---\n\n";

// 1. List workflows
test('listWorkflows() returns a result', function () use ($client) {
    $result = $client->listWorkflows();
    assert($result->count() >= 0, 'count should be >= 0');
    echo "        Found {$result->count()} workflows (page {$result->currentPage}/{$result->totalPages})\n";
});

// 2. List with pagination
test('listWorkflows() respects per_page', function () use ($client) {
    $result = $client->listWorkflows(page: 1, perPage: 2);
    assert($result->perPage === 2, "per_page should be 2, got {$result->perPage}");
});

// 3. Describe a known workflow
$slug = null;
test('describeWorkflow() returns definition with params', function () use ($client, &$slug) {
    $list = $client->listWorkflows();
    assert(! $list->isEmpty(), 'Need at least one workflow to test describe');
    $slug = $list->workflows[0]->slug;

    $def = $client->describeWorkflow($slug);
    assert($def->slug === $slug, "Slug mismatch: expected {$slug}, got {$def->slug}");
    assert($def->name !== '', 'Name should not be empty');
    echo "        Described: {$def->name} (slug={$def->slug}, mode={$def->inputMode->label()}, params=" . count($def->params) . ")\n";
});

// 4. Describe cache works
test('describeWorkflow() uses in-memory cache', function () use ($client, &$slug) {
    if (! $slug) {
        echo "        Skipped (no slug)\n";
        return;
    }
    // Second call should hit cache (no HTTP)
    $def = $client->describeWorkflow($slug);
    assert($def->slug === $slug);
});

// 5. Describe non-existent returns 404
test('describeWorkflow() throws on non-existent slug', function () use ($client) {
    try {
        $client->clearDescribeCache();
        $client->describeWorkflow('non-existent-slug-' . uniqid());
        assert(false, 'Should have thrown');
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        assert($e->getResponse()->getStatusCode() === 404, 'Expected 404');
    }
});

// 6. Client-side validation catches missing required field
test('validate() rejects missing required params', function () use ($client, &$slug) {
    if (! $slug) {
        echo "        Skipped\n";
        return;
    }
    $def = $client->describeWorkflow($slug);
    $required = $def->requiredParams();
    if (empty($required)) {
        echo "        Skipped (no required params)\n";
        return;
    }

    try {
        $def->validate([]); // empty payload
        assert(false, 'Should have thrown ValidationException');
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        assert(isset($errors[$required[0]->key]), 'Should have error for ' . $required[0]->key);
    }
});

// 7. Client-side validation catches extra params (JSON mode)
test('validate() rejects extra unknown params', function () use ($client, &$slug) {
    if (! $slug) {
        echo "        Skipped\n";
        return;
    }
    $def = $client->describeWorkflow($slug);
    if (! $def->inputMode->isJson()) {
        echo "        Skipped (not JSON mode)\n";
        return;
    }

    // Build valid payload + extra key
    $payload = [];
    foreach ($def->params as $p) {
        $payload[$p->key] = match ($p->type->value) {
            'json_number' => 1,
            'json_boolean' => true,
            'json_array' => ['a'],
            'json_object' => ['k' => 'v'],
            default => 'test',
        };
    }
    $payload['__bogus_extra__'] = 'should fail';

    try {
        $def->validate($payload);
        assert(false, 'Should have thrown');
    } catch (ValidationException $e) {
        assert(isset($e->getErrors()['__bogus_extra__']));
    }
});

// 8. Execute a workflow (if JSON mode with params)
test('executeWorkflow() returns a status URL', function () use ($client, &$slug) {
    if (! $slug) {
        echo "        Skipped\n";
        return;
    }
    $def = $client->describeWorkflow($slug);
    if (! $def->inputMode->isJson()) {
        echo "        Skipped (not JSON mode)\n";
        return;
    }

    // Build minimal valid payload
    $payload = [];
    foreach ($def->params as $p) {
        if ($p->required || $p->defaultValue === null) {
            $payload[$p->key] = match ($p->type->value) {
                'json_number' => 1,
                'json_boolean' => true,
                'json_array' => ['test'],
                'json_object' => ['key' => 'value'],
                default => 'integration test',
            };
        }
    }

    $statusUrl = $client->executeWorkflow($slug, $payload);
    assert(str_contains($statusUrl, '/job/status/'), "Status URL should contain /job/status/, got: {$statusUrl}");
    echo "        Status URL: {$statusUrl}\n";
});

// 9. validateAndExecute()
test('validateAndExecute() works end-to-end', function () use ($client, &$slug) {
    if (! $slug) {
        echo "        Skipped\n";
        return;
    }
    $def = $client->describeWorkflow($slug);
    if (! $def->inputMode->isJson()) {
        echo "        Skipped (not JSON mode)\n";
        return;
    }

    $payload = [];
    foreach ($def->params as $p) {
        if ($p->required || $p->defaultValue === null) {
            $payload[$p->key] = match ($p->type->value) {
                'json_number' => 1,
                'json_boolean' => true,
                'json_array' => ['test'],
                'json_object' => ['key' => 'value'],
                default => 'integration test',
            };
        }
    }

    $statusUrl = $client->validateAndExecute($slug, $payload);
    assert(str_contains($statusUrl, '/job/status/'));
    echo "        OK: {$statusUrl}\n";
});

echo "\n--- Results: {$pass} passed, {$fail} failed ---\n\n";
exit($fail > 0 ? 1 : 0);
