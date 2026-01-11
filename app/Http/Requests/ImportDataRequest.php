<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportDataRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'connection' => [
                'required',
                'string',
                'in:' . implode(',', array_keys(config('database.connections')))
            ],
            'table' => [
                'required',
                'string',
                'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
                'max:64'
            ],
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:10240' // 10MB max
            ]
        ];
    }

    public function messages()
    {
        return [
            'connection.in' => 'Selected database connection is not configured.',
            'table.regex' => 'Table name can only contain letters, numbers, and underscores, and must start with a letter or underscore.',
            'file.mimes' => 'The file must be a file of type: csv, xlsx, xls.',
            'file.max' => 'The file may not be greater than 10MB.'
        ];
    }
}
