<?php

declare(strict_types=1);

namespace SharpAPI\CustomWorkflow\Enums;

enum InputMode: string
{
    case JSON = 'application/json';
    case FORM_DATA = 'multipart/form-data';

    public function label(): string
    {
        return match ($this) {
            self::JSON => 'JSON',
            self::FORM_DATA => 'Form-Data',
        };
    }

    public function isJson(): bool
    {
        return $this === self::JSON;
    }

    public function isFormData(): bool
    {
        return $this === self::FORM_DATA;
    }
}
