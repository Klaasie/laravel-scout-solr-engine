<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->get('title');
    }

    public function getAuthor(): string
    {
        return $this->get('author');
    }

    public function getPublicationDate(): string
    {
        return $this->get('publication_date');
    }

    public function getSummary(): string
    {
        return $this->get('summary');
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'author' => 'required',
            'publication_date' => 'required|date_format:Y-m-d',
            'summary' => 'required',
        ];
    }
}
