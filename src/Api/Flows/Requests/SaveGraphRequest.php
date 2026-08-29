<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveGraphRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'graph' => ['required', 'array'],
            'graph.nodes' => ['array'],
            'graph.edges' => ['array'],
        ];
    }
}
