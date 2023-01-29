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
        if ($this->isEmptyString('name')) {
            return null;
        }

        return $this->get('name');
    }

    public function getEmail(): ?string
    {
        if ($this->isEmptyString('email')) {
            return null;
        }

        return $this->get('email');
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [];
    }
}
