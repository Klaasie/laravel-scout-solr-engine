<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

class Book extends Model
{
    use HasFactory, Searchable;

    public $fillable = [
        'title',
        'author',
        'publication_date',
        'summary',
    ];

    protected $casts = [
        'publication_date' => 'datetime',
    ];

    public function getId(): int
    {
        return $this->getAttribute('id');
    }

    public function getTitle(): string
    {
        return $this->getAttribute('title');
    }

    public function getAuthor(): string
    {
        return $this->getAttribute('author');
    }

    public function getPublicationDate(): Carbon
    {
        return $this->getAttribute('publication_date');
    }

    public function getSummary(): string
    {
        return $this->getAttribute('summary');
    }
}
