<?php

declare(strict_types=1);

namespace SharpAPI\CustomWorkflow\Enums;

enum ParamType: string
{
    case FORM_DATA_TEXT = 'form_data_text';
    case FORM_DATA_FILE = 'form_data_file';
    case JSON_STRING = 'json_string';
    case JSON_NUMBER = 'json_number';
    case JSON_OBJECT = 'json_object';
    case JSON_ARRAY = 'json_array';
    case JSON_BOOLEAN = 'json_boolean';

    public function label(): string
    {
        return match ($this) {
            self::FORM_DATA_TEXT => 'Form-Data Text',
            self::FORM_DATA_FILE => 'Form-Data File',
            self::JSON_STRING => 'JSON String',
            self::JSON_NUMBER => 'JSON Number',
            self::JSON_BOOLEAN => 'JSON Boolean',
            self::JSON_OBJECT => 'JSON Object',
            self::JSON_ARRAY => 'JSON Array',
        };
    }

    public function isJsonType(): bool
    {
        return in_array($this, [
            self::JSON_STRING,
            self::JSON_NUMBER,
            self::JSON_BOOLEAN,
            self::JSON_OBJECT,
            self::JSON_ARRAY,
        ], true);
    }

    public function isFormDataType(): bool
    {
        return in_array($this, [
            self::FORM_DATA_TEXT,
            self::FORM_DATA_FILE,
        ], true);
    }
}
