<?php

declare(strict_types=1);

namespace SharpAPI\CustomWorkflow\Exceptions;

use InvalidArgumentException;

class ValidationException extends InvalidArgumentException
{
    /** @var array<string, list<string>> */
    private array $errors;

    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;

        $messages = [];
        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = "{$field}: {$error}";
            }
        }

        parent::__construct('Validation failed: ' . implode('; ', $messages));
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
