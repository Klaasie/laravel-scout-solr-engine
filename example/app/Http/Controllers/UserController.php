<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'users' => User::search('*:*')->paginate(15)->onEachSide(1),
        ]);
    }
}
