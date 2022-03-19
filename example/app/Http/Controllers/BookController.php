<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookFilterPostRequest;
use App\Models\Book;
use App\Traits\HasMenu;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Scout\Solr\Events\BeforeSelect;

class BookController extends Controller
{
    use HasMenu;

    public const MENU = 'books';

    private Factory $view;
    private Dispatcher $dispatcher;

    public function __construct(Factory $view, Dispatcher $dispatcher)
    {
        $this->view = $view;
        $this->dispatcher = $dispatcher;
    }

    public function index(BookFilterPostRequest $request): View
    {
        if ($request->anyFilled(['title', 'author', 'publication_date', 'summary'])) {
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

            if ($publicationDateTo = $request->getPublicationDate()) {
                // SOLR requires a ISO_8601 date format. The default scout builder does not suffice.
                // We'll hook into the before select event to use the select query helper to format the date properly
                // and attach it to the query string.
                $this->dispatcher->listen(
                    BeforeSelect::class,
                    static function (BeforeSelect $event) use ($publicationDateTo) {
                        $event->query->setQuery(
                            implode(
                                ' AND ',
                                array_filter([
                                    $event->query->getQuery(),
                                    sprintf(
                                        'publication_date:"%s"',
                                        $event->query->getHelper()->formatDate($publicationDateTo)
                                    ),
                                ])
                            )
                        );
                    }
                );
            }
        } else {
            $query = Book::search('*:*');
        }

        $query->orderBy('title_str');

        $this->setMenu(self::MENU);

        return $this->view->make('books.index', [
            'books' => $query->paginate(12)->appends($request->query())->onEachSide(1),
            'title' => $request->getTitle(),
            'author' => $request->getAuthor(),
            'publicationDate' => $request->getPublicationDate(),
            'summary' => $request->getSummary(),
        ]);
    }

    public function create()
    {
        // ..
    }

    public function store()
    {
        // ..
    }

    public function edit()
    {
        // ..
    }

    public function update()
    {
        // ..
    }

    public function destroy()
    {
        // ..
    }
}
