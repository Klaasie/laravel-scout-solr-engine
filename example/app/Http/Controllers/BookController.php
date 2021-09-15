<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookFilterPostRequest;
use App\Models\Book;
use Illuminate\Contracts\View\View;

class BookController extends Controller
{
    public function __invoke(BookFilterPostRequest $request): View
    {
        if ($request->hasAny(['title', 'author', 'publication_date_from', 'publication_date_to', 'summary'])) {
            $query = Book::search('');

            if ($name = $request->getTitle()) {
                $query->where('title', $name);
            }

            if ($email = $request->getAuthor()) {
                $query->where('author', $email);
            }

            if ($summary = $request->getSummary()) {
                $query->where('summary', $summary);
            }
        } else {
            $query = Book::search('*:*');
        }

        $query->orderBy('title_str');

        return view('books', [
            'books' => $query->paginate(12)->appends($request->query())->onEachSide(1),
            'title' => $request->getTitle(),
            'author' => $request->getAuthor(),
            'publication_date_from' => $request->getPublicationDateFrom(),
            'publication_date_to' => $request->getPublicationDateTo(),
            'summary' => $request->getSummary(),
            'menu' => 'books',
        ]);
    }
}
