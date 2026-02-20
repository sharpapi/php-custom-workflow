<?php

declare(strict_types=1);

namespace SharpAPI\CustomWorkflow\Validation;

use SharpAPI\CustomWorkflow\DTO\WorkflowDefinition;
use SharpAPI\CustomWorkflow\DTO\WorkflowParam;
use SharpAPI\CustomWorkflow\Enums\InputMode;
use SharpAPI\CustomWorkflow\Enums\ParamType;
use SharpAPI\CustomWorkflow\Exceptions\ValidationException;

class PayloadValidator
{
    /**
     * Validate a payload against a workflow definition.
     * Mirrors server-side validation exactly.
     *
     * @param WorkflowDefinition $workflow
     * @param array $params  JSON key-value pairs (JSON mode) or text fields (form-data mode)
     * @param array $files   File paths keyed by param name (form-data mode only)
     *
     * @throws ValidationException
     */
    public static function validate(WorkflowDefinition $workflow, array $params = [], array $files = []): void
    {
        if ($workflow->inputMode === InputMode::JSON) {
            self::validateJsonMode($workflow, $params);
        } else {
            self::validateFormDataMode($workflow, $params, $files);
        }
    }

    /**
     * @throws ValidationException
     */
    private static function validateJsonMode(WorkflowDefinition $workflow, array $params): void
    {
        $errors = [];

        foreach ($workflow->params as $param) {
            $has = array_key_exists($param->key, $params);

            if (! $has && $param->required) {
                $errors[$param->key] = ['Field is required'];
                continue;
            }

            if ($has) {
                $typeError = self::checkJsonType($param, $params[$param->key]);
                if ($typeError !== null) {
                    $errors[$param->key] = [$typeError];
                }
            }
        }

        // Reject extra/unknown parameters
        $definedKeys = array_map(fn (WorkflowParam $p) => $p->key, $workflow->params);
        $extraKeys = array_diff(array_keys($params), $definedKeys);
        foreach ($extraKeys as $k) {
            $errors[$k] = ['Unknown parameter'];
        }

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @throws ValidationException
     */
    private static function validateFormDataMode(WorkflowDefinition $workflow, array $params, array $files): void
    {
        $errors = [];

        foreach ($workflow->params as $param) {
            if ($param->type === ParamType::FORM_DATA_FILE) {
                $hasFile = isset($files[$param->key]) && $files[$param->key] !== '';
                if (! $hasFile && $param->required) {
                    $errors[$param->key] = ['File is required'];
                    continue;
                }
                if ($hasFile && ! is_readable($files[$param->key])) {
                    $errors[$param->key] = ['File is not readable: ' . $files[$param->key]];
                }
            } else {
                $val = $params[$param->key] ?? null;
                if ($param->required && ($val === null || $val === '')) {
                    $errors[$param->key] = ['Field is required'];
                }
            }
        }

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private static function checkJsonType(WorkflowParam $param, mixed $value): ?string
    {
        return match ($param->type) {
            ParamType::JSON_STRING => is_string($value) ? null : 'Must be a string',
            ParamType::JSON_NUMBER => (is_int($value) || is_float($value)) ? null : 'Must be a number',
            ParamType::JSON_BOOLEAN => is_bool($value) ? null : 'Must be a boolean',
            ParamType::JSON_OBJECT => (is_array($value) && !array_is_list($value)) ? null : 'Must be an object',
            ParamType::JSON_ARRAY => (is_array($value) && array_is_list($value)) ? null : 'Must be an array',
            default => null,
        };
    }
}
