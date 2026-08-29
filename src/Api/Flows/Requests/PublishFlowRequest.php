<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['change_summary' => ['nullable', 'string', 'max:255']];
    }
}
