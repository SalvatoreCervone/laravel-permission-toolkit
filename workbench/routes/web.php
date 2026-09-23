<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Workbench\App\Models\User;

Route::get('/', function () {
    return redirect('/permission-manager');
});

Route::get('/login', function () {
    if (! Auth::check()) {
        $user = User::first();
        if ($user) {
            Auth::login($user);
        }
    }
    return redirect('/permission-manager');
})->name('login');
