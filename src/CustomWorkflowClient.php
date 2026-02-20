<?php

declare(strict_types=1);

namespace SharpAPI\CustomWorkflow;

use GuzzleHttp\Exception\GuzzleException;
use SharpAPI\Core\Client\SharpApiClient;
use SharpAPI\Core\Exceptions\ApiException;
use SharpAPI\CustomWorkflow\DTO\WorkflowDefinition;
use SharpAPI\CustomWorkflow\DTO\WorkflowListResult;

class CustomWorkflowClient extends SharpApiClient
{
    /** @var array<string, WorkflowDefinition> */
    private array $describeCache = [];

    public function __construct(
        string $apiKey,
        ?string $apiBaseUrl = 'https://sharpapi.com/api/v1',
        ?string $userAgent = 'SharpAPIPHPCustomWorkflow/1.0.0'
    ) {
        parent::__construct($apiKey, $apiBaseUrl, $userAgent);
    }

    /**
     * List authenticated user's workflows, paginated.
     *
     * @param int $page     Page number (default 1)
     * @param int $perPage  Items per page (default 15, max 100)
     *
     * @throws GuzzleException|ApiException
     * @api
     */
    public function listWorkflows(int $page = 1, int $perPage = 15): WorkflowListResult
    {
        $queryParams = [
            'page' => $page,
            'per_page' => min($perPage, 100),
        ];

        $response = $this->makeGetRequest('/custom', $queryParams);
        $data = json_decode($response->getBody()->__toString(), true);

        return WorkflowListResult::fromArray($data);
    }

    /**
     * Describe a single workflow — returns metadata, params, input_mode, output_schema.
     * Results are cached in-memory for the lifetime of this client instance.
     *
     * @throws GuzzleException|ApiException
     * @api
     */
    public function describeWorkflow(string $slug): WorkflowDefinition
    {
        if (isset($this->describeCache[$slug])) {
            return $this->describeCache[$slug];
        }

        $response = $this->makeGetRequest('/custom/' . $slug);
        $data = json_decode($response->getBody()->__toString(), true);

        $definition = WorkflowDefinition::fromArray($data['data']);
        $this->describeCache[$slug] = $definition;

        return $definition;
    }

    /**
     * Clear the in-memory describe cache for one or all workflows.
     *
     * @api
     */
    public function clearDescribeCache(?string $slug = null): void
    {
        if ($slug !== null) {
            unset($this->describeCache[$slug]);
        } else {
            $this->describeCache = [];
        }
    }

    /**
     * Execute a workflow. For JSON mode, pass $params as key-value pairs.
     * For form-data mode, pass text fields in $params and file paths in $files.
     *
     * Returns the status URL for polling with fetchResults().
     *
     * @param string $slug    Workflow slug
     * @param array  $params  Key-value data (JSON fields or form-data text fields)
     * @param array  $files   File paths keyed by param name (form-data mode only)
     *
     * @throws GuzzleException|ApiException
     * @api
     */
    public function executeWorkflow(string $slug, array $params = [], array $files = []): string
    {
        if (! empty($files)) {
            $response = $this->makeMultipartRequest('/custom/' . $slug, $params, $files);
        } else {
            $response = $this->makeRequest('POST', '/custom/' . $slug, $params);
        }

        return $this->parseStatusUrl($response);
    }

    /**
     * Describe the workflow, validate the payload client-side, then execute.
     * This is the safest way to call a workflow — catches validation errors before
     * making the POST request.
     *
     * @param string $slug    Workflow slug
     * @param array  $params  Key-value data
     * @param array  $files   File paths keyed by param name (form-data mode only)
     *
     * @throws GuzzleException|ApiException
     * @throws \SharpAPI\CustomWorkflow\Exceptions\ValidationException
     * @api
     */
    public function validateAndExecute(string $slug, array $params = [], array $files = []): string
    {
        $definition = $this->describeWorkflow($slug);
        $definition->validate($params, $files);

        return $this->executeWorkflow($slug, $params, $files);
    }

    /**
     * Send a multipart/form-data POST request with arbitrary named file fields.
     * The base makeRequest() only supports a single 'file' field name,
     * but custom workflows can have multiple file fields with custom names.
     *
     * @throws GuzzleException|ApiException
     */
    private function makeMultipartRequest(string $url, array $data, array $files): \Psr\Http\Message\ResponseInterface
    {
        $multipart = [];

        // Add text fields
        foreach ($data as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => is_array($value) ? json_encode($value) : (string) $value,
            ];
        }

        // Add file fields with their proper param names
        foreach ($files as $fieldName => $filePath) {
            $multipart[] = [
                'name' => $fieldName,
                'contents' => file_get_contents($filePath),
                'filename' => basename($filePath),
            ];
        }

        $options = [
            'headers' => $this->getHeaders(),
            'multipart' => $multipart,
        ];

        return $this->executeWithRateLimitRetry('POST', $this->getApiBaseUrl() . $url, $options);
    }
}
