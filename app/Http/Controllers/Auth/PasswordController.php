<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {

        // Валидация входящих данных
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'], // Проверка текущего пароля
            'password' => ['required', Password::defaults(), 'confirmed'], // Новый пароль с проверкой и подтверждением
        ]);

        // Получение текущего пользователя
        $user = $request->user();

        // Обновление пароля пользователя
        $user->update([
            'password' => Hash::make($validated['password']), // Хэшированный пароль
        ]);

        // Получение ID пользователя
        $userId = $user->id;

        // Обновление данных в таблице `clients`
        if (DB::table('clients')->where('user_id', $userId)->exists()) {
            // Если запись существует, выполняем обновление оригинального пароля
            DB::table('clients')->where('user_id', $userId)->update([
                'original_password' => $validated['password'], // Некриптованная версия нового пароля
            ]);
        }

        // Возврат на предыдущую страницу
        return back();
    }
}
