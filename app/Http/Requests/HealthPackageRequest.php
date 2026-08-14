<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HealthPackageRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $package = $this->route('health_package');

        return [
            'package_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('health_packages', 'package_id')->ignore($package?->id ?? null),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'sub_category' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string', 'distinct', 'max:255'],
        ];
    }
}