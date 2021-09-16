<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserFilterPostRequest;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class UserController extends Controller
{
    private Factory $view;

    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    public function __invoke(UserFilterPostRequest $request): View
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

        return $this->view->make('users', [
            'users' => $query->paginate(10)->appends($request->query())->onEachSide(1),
            'name' => $request->getName(),
            'email' => $request->getEmail(),
            'menu' => 'users',
        ]);
    }
}
