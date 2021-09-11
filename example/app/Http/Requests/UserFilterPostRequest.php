<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserFilterPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getEmail(): ?string
    {
        return $this->get('email');
    }

    public function rules(): array
    {
        return [];
    }
}
