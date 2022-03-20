<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookCreate;
use App\Http\Requests\BookFilterPostRequest;
use App\Http\Requests\BookUpdate;
use App\Models\Book;
use App\Traits\HasMenu;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
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
            $query = Book::search();

            if ($name = $request->getTitle()) {
                $query->where('title', $name);
            }

            if ($email = $request->getAuthor()) {
                $query->where('author', $email);
            }

            if ($summary = $request->getSummary()) {
                $query->where('summary', $summary);
            }

            if ($publicationDate = $request->getPublicationDate()) {
                // SOLR requires a ISO_8601 date format. The default scout builder does not suffice.
                // We'll hook into the before select event to use the select query helper to format the date properly
                // and attach it to the query string.
                $this->dispatcher->listen(
                    BeforeSelect::class,
                    static function (BeforeSelect $event) use ($publicationDate) {
                        $event->query->setQuery(
                            implode(
                                ' AND ',
                                array_filter([
                                    $event->query->getQuery(),
                                    sprintf(
                                        'publication_date:"%s"',
                                        $event->query->getHelper()->formatDate($publicationDate)
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

    public function create(): View
    {
        $this->setMenu(self::MENU);

        return $this->view->make('books.create');
    }

    public function store(BookCreate $request, Redirector $redirector): RedirectResponse
    {
        Book::query()->create($request->only(['title', 'author', 'publication_date', 'summary']));

        return $redirector->route('books.index')
            ->with('success', 'Book created');
    }

    public function edit(string $id): View
    {
        $book = Book::query()
            ->find($id);

        abort_if($book === null, 404);

        $this->setMenu(self::MENU);

        return $this->view->make('books.update', [
            'book' => $book,
        ]);
    }

    public function update(int $id, BookUpdate $request, Redirector $redirector): RedirectResponse
    {
        $book = Book::query()->findOrFail($id);
        $book->update($request->only(['title', 'author', 'publication_date', 'summary']));

        return $redirector->route('books.index')
            ->with('success', 'Book updated');
    }

    public function destroy(int $id, Redirector $redirector): RedirectResponse
    {
        $book = Book::query()
            ->findOrFail($id);
        $book->delete();

        return $redirector->route('books.index')
            ->with('success', 'Book deleted');
    }
}
