<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserFilterPostRequest;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Redirector;

class UserController extends Controller
{
    public function index(UserFilterPostRequest $request, Factory $view): View
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

        return $view->make('users', [
            'users' => $query->paginate(10)->appends($request->query())->onEachSide(1),
            'name' => $request->getName(),
            'email' => $request->getEmail(),
            'menu' => 'users',
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

    public function edit()
    {
        // ..
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
