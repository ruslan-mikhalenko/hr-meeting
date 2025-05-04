<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    public function __construct()
    {

        // Проверяем аутентификацию и роли через middleware
        $this->middleware('auth');
        $this->middleware('checkRole:super-admin,admin,moderator');
    }



    public function test()
    {

        $user_auth = Auth::user();

        // Возвращаем представление для всех ролей, так как проверка уже проведена на уровне middleware
        return Inertia::render('Dashboard', [

            'user_auth' => $user_auth,

            // Другие данные, если необходимо
        ]);
    }
}
