<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['payload' => ['nullable', 'array']];
    }
}
