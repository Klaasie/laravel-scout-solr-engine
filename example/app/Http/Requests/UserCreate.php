<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCreate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->get('name');
    }

    public function getEmail(): string
    {
        return $this->get('email');
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ];
    }
}
