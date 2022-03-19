<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserCreate;
use App\Http\Requests\UserFilterPostRequest;
use App\Http\Requests\UserUpdate;
use App\Models\User;
use App\Traits\HasMenu;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class UserController extends Controller
{
    use HasMenu;

    public const MENU = 'users';

    private Factory $view;

    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    public function index(UserFilterPostRequest $request): View
    {
        if ($request->anyFilled(['name', 'email'])) {
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

        $this->setMenu(self::MENU);

        return $this->view->make('users.index', [
            'users' => $query->paginate(10)
                ->appends($request->query())
                ->onEachSide(1),
            'name' => $request->getName(),
            'email' => $request->getEmail(),
        ]);
    }

    public function create()
    {
        $this->setMenu(self::MENU);

        return $this->view->make('users.create');
    }

    public function store(UserCreate $request, Redirector $redirector): RedirectResponse
    {
        User::query()->create($request->only(['name', 'email', 'password']));

        return $redirector->route('users.index')
            ->with('success', 'User created');
    }

    public function edit(string $id): View
    {
        $user = User::query()
            ->find($id);

        abort_if($user === null, 404);

        $this->setMenu(self::MENU);

        return $this->view->make('users.update', [
            'user' => $user,
        ]);
    }

    public function update(int $id, UserUpdate $request, Redirector $redirector): RedirectResponse
    {
        User::query()->where('id', '=', $id)
            ->update($request->only(['name', 'email']));

        return $redirector->route('users.index')
            ->with('success', 'User updated');
    }

    public function destroy(int $id, Redirector $redirector): RedirectResponse
    {
        $user = User::query()
            ->findOrFail($id);
        $user->delete();

        return $redirector->route('users.index')
            ->with('success', 'User deleted');
    }
}
