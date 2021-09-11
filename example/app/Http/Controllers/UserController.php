<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserFilterPostRequest;
use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
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

        return view('welcome', [
            'users' => $query->paginate(10)->appends($request->query())->onEachSide(1),
            'name' => $request->getName(),
            'email' => $request->getEmail()
        ]);
    }
}
