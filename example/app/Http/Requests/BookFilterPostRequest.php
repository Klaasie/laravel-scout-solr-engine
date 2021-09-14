<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookFilterPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getTitle(): ?string
    {
        return $this->get('title');
    }

    public function getAuthor(): ?string
    {
        return $this->get('author');
    }

    public function getPublicationDateFrom(): ?string
    {
        return $this->get('publication_date_from');
    }

    public function getPublicationDateTo(): ?string
    {
        return $this->get('publication_date_to');
    }

    public function getSummary(): ?string
    {
        return $this->get('summary');
    }

    public function rules(): array
    {
        return [];
    }
}
