<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('url') && ! preg_match('/^https?:\/\//i', $this->url)) {
            $this->merge([
                'url' => 'https://'.$this->url,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255', 'url', 'regex:/^https?:\/\//i'],
            'check_interval' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.url' => 'Please enter a valid website URL, for example https://example.com.',
            'url.regex' => 'The URL must start with http:// or https://.',
            'check_interval.min' => 'The check interval must be at least 1 hour.',
        ];
    }
}
