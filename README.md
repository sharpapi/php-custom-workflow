![SharpAPI GitHub cover](https://sharpapi.com/sharpapi-github-laravel-bg.jpg "SharpAPI PHP Client")

# SharpAPI PHP Custom Workflow SDK

## 🎯 Build and execute no-code AI API endpoints — powered by SharpAPI Custom AI Workflows.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sharpapi/php-custom-workflow.svg?style=flat-square)](https://packagist.org/packages/sharpapi/php-custom-workflow)
[![Total Downloads](https://img.shields.io/packagist/dt/sharpapi/php-custom-workflow.svg?style=flat-square)](https://packagist.org/packages/sharpapi/php-custom-workflow)

Check the full documentation on the [Custom AI Workflows](https://sharpapi.com/en/catalog/ai/custom-workflows) page.

---

## Quick Links

| Resource | Link |
|----------|------|
| **Main API Documentation** | [Authorization, Webhooks, Polling & More](https://documenter.getpostman.com/view/31106842/2s9Ye8faUp) |
| **Product Details** | [SharpAPI.com](https://sharpapi.com/en/catalog/ai/custom-workflows) |
| **SDK Libraries** | [GitHub - SharpAPI SDKs](https://github.com/sharpapi) |

---

## Requirements

- PHP >= 8.1
- A SharpAPI account with an API key

---

## Installation

### Step 1. Install the package via Composer:

```bash
composer require sharpapi/php-custom-workflow
```

### Step 2. Visit [SharpAPI](https://sharpapi.com/) to get your API key.

---

## Laravel Integration

Building a Laravel application? Check the Laravel package version: https://github.com/sharpapi/laravel-custom-workflow

---

## What it does

This package provides a PHP SDK for SharpAPI Custom AI Workflows — user-built, no-code AI API endpoints. It lets you:

- **List** your custom workflows
- **Describe** a workflow's schema (params, input mode, output schema)
- **Execute** workflows with JSON or form-data payloads (including file uploads)
- **Validate** payloads client-side before execution
- **Poll** for async results

---

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use SharpAPI\CustomWorkflow\CustomWorkflowClient;
use GuzzleHttp\Exception\GuzzleException;

$client = new CustomWorkflowClient(apiKey: 'your_api_key_here');

try {
    // Execute a JSON workflow
    $statusUrl = $client->executeWorkflow('my-sentiment-analyzer', [
        'text' => 'Great product!',
        'score' => 4.5,
    ]);

    // Optional: adjust polling settings
    $client->setApiJobStatusPollingInterval(10); // seconds
    $client->setApiJobStatusPollingWait(180);    // seconds total wait

    // Fetch results when ready
    $result = $client->fetchResults($statusUrl)->toArray();
    print_r($result);
} catch (GuzzleException $e) {
    echo $e->getMessage();
}
```

### Execute a form-data workflow with file upload

```php
$statusUrl = $client->executeWorkflow('document-analyzer',
    params: ['description' => 'Annual report'],
    files: ['document' => '/path/to/file.pdf'],
);
$result = $client->fetchResults($statusUrl);
```

### Discover available workflows

```php
$workflows = $client->listWorkflows();

foreach ($workflows->workflows as $wf) {
    echo "{$wf->name} — /api/v1/custom/{$wf->slug}\n";
}
```

### Inspect a workflow's schema

```php
$wf = $client->describeWorkflow('my-sentiment-analyzer');

echo $wf->inputMode->label();           // "JSON"
echo count($wf->requiredParams());       // 1
echo json_encode($wf->outputSchema);     // {"sentiment":"string","confidence":"number"}
```

### Client-side validation before execution

```php
use SharpAPI\CustomWorkflow\Exceptions\ValidationException;

try {
    // Fetches schema, validates params, then executes — one call
    $statusUrl = $client->validateAndExecute('my-analyzer', ['text' => 'hello']);
} catch (ValidationException $e) {
    print_r($e->getErrors());
    // ["unknown_field" => ["Unknown parameter"]]
}
```

---

## API Reference

`CustomWorkflowClient` extends `SharpApiClient` from [sharpapi/php-core](https://github.com/sharpapi/php-core), inheriting `fetchResults()`, `ping()`, `quota()`, rate limiting, etc.

| Method | Returns | Description |
|--------|---------|-------------|
| `listWorkflows($page, $perPage)` | `WorkflowListResult` | Paginated list of your workflows |
| `describeWorkflow($slug)` | `WorkflowDefinition` | Schema, params, input mode, output schema |
| `executeWorkflow($slug, $params, $files)` | `string` (status URL) | Execute a workflow |
| `validateAndExecute($slug, $params, $files)` | `string` (status URL) | Validate client-side, then execute |
| `fetchResults($statusUrl)` | `SharpApiJob` | Poll for results (inherited) |

### DTOs

- **`WorkflowDefinition`** — `slug`, `name`, `inputMode`, `outputSchema`, `params[]`, `requiredParams()`, `optionalParams()`
- **`WorkflowParam`** — `key`, `label`, `type`, `required`, `defaultValue`
- **`WorkflowListResult`** — `workflows[]`, `total`, `perPage`, `currentPage`, `totalPages`

### Enums

- **`InputMode`** — `JSON` / `FORM_DATA`
- **`ParamType`** — `JSON_STRING`, `JSON_NUMBER`, `JSON_BOOLEAN`, `JSON_OBJECT`, `JSON_ARRAY`, `FORM_DATA_TEXT`, `FORM_DATA_FILE`

---

### Do you think our API is missing some obvious functionality?

- [Please let us know via GitHub »](https://github.com/sharpapi/php-custom-workflow/issues)

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## Credits

- [A2Z WEB LTD](https://github.com/a2zwebltd)
- [Dawid Makowski](https://github.com/makowskid)
- Boost your [PHP AI](https://sharpapi.com/) capabilities!

---

## License

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE.md)

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---
## Social Media

🚀 For the latest news, tutorials, and case studies, don't forget to follow us on:
- [SharpAPI X (formerly Twitter)](https://x.com/SharpAPI)
- [SharpAPI YouTube](https://www.youtube.com/@SharpAPI)
- [SharpAPI Vimeo](https://vimeo.com/SharpAPI)
- [SharpAPI LinkedIn](https://www.linkedin.com/products/a2z-web-ltd-sharpapicom-automate-with-aipowered-api/)
- [SharpAPI Facebook](https://www.facebook.com/profile.php?id=61554115896974)
