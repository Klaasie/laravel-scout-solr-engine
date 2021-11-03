<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserFilterPostRequest;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Redirector;

class UserController extends Controller
{
    private Factory $view;

    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    public function index(UserFilterPostRequest $request): View
    {
        if ($request->hasAny(['name', 'email'])) {
            $query = User::search('');

            if ($name = $request->getName()) {
                $query->where('name', $name);
            }

            if ($email = $request->getEmail()) {
                $query->where('email', $email);
            }
        } else {
            $query = User::search('*:*');
        }

        $this->view->composer('components.menu', static function (View $view) {
            $view->with('menu', 'users');
        });

        return $this->view->make('users.index', [
            'users' => $query->paginate(10)->appends($request->query())->onEachSide(1),
            'name' => $request->getName(),
            'email' => $request->getEmail(),
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

    public function show()
    {
        // ..
    }

    public function edit(string $id): View
    {
        $user = User::query()->find($id);

        abort_if($user === null, 404);

        return $this->view->make('users', [
            'user' => $user,
            'menu' => 'users',
        ]);
    }

    public function update()
    {
        // ..
    }

    public function destroy(int $id, Redirector $redirector)
    {
        $user = User::query()->findOrFail($id);
        $user->delete();

        return $redirector->route('users.index')->with('success', 'User deleted');
    }
}
