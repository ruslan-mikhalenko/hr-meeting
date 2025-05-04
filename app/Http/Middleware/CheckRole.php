<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        // Если пользователь не авторизован, выдаем ошибку доступа
        if (!$user) {
            abort(403, 'Доступ запрещен.');
        }

        // Проверяем, имеет ли пользователь доступ по любому из заданных ролей через Gates
        foreach ($roles as $role) {
            if (Gate::allows("access-{$role}")) {
                return $next($request);
            }
        }

        // Если не пройдена ни одна из проверок ролей
        abort(403, 'Доступ запрещен.');
    }
}
