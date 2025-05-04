<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Mail\UserRegistered;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

use Illuminate\Support\Str;


use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {

        // Проверяем роль и переопределяем unp
        if (!$request->role) {
            $request->merge(['role' => 'client']);
        }


        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,

            'role' => 'required',
        ]);




        // Проверка на существование пользователя с аналогичным email и unp
        $existingAgency = DB::table('clients')
            ->where('original_email', $validatedData['email'])

            ->first();

        if ($existingAgency) {
            // Если запрос был выполнен с Ajax (например, из вашего Vue приложения)
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Позиция с таким email уже существует.'], 409);
            }

            // Для обычных запросов возвращаем редирект с сообщением
            return redirect()->back()->withErrors(['message' => 'Позиция с таким email уже существует.']);
        }





        // Генерация уникального пароля из утилиты
        $password_random = $this->generateUniquePassword();

        $hashedPassword = bcrypt($password_random);

        /* dd($request->role); */

        // Создаем пользователя с сгенерированным паролем
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $hashedPassword,
            'role' => $request->role,
            'is_active' => 1,
        ]);


        $userId = $user->id; // Получение ID созданного пользователя



        DB::table('clients')->insert([
            'user_id' => $userId,
            'name' => $validatedData['name'],
            'original_email' => $validatedData['email'],

            'original_password' => $password_random,
            'created_at' => now(), // Установка даты создания
            'updated_at' => now(), // Установка даты обновления
        ]);



        // Отправка пароля на указанный email
        Mail::to($user->email)->send(new UserRegistered($user, $password_random));

        // Формирование сообщения
        $text = "Запомните Ваши данные для входа в систему.<br><br>" .
            "Ваш логин: " . htmlspecialchars($validatedData['email']) . "<br>" .
            "Ваш пароль: " . htmlspecialchars($password_random) . "<br><br>" .
            "<b style='font-size:14px'>Дополнительно пароль отправлен на указанный email</b>
            <br><br>Теперь вы можете войти под своими данными";


        // Авторизуем пользователя
        //Auth::login($user);


        return redirect('/login')->with('message', $text);

        /*  return redirect(RouteServiceProvider::HOME); */
    }



    public static function generateUniquePassword($length = 8)
    {
        // Можно использовать различные наборы символов для создания пароля
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $specialCharacters = '!@#$%^&*()-_=+[]{};:,.<>?';

        // Создаем пароль, который состоит из случайных символов
        $password = Str::random(4) . // 4 случайных символа
            substr(str_shuffle($letters), 0, 2) . // 2 случайные буквы
            substr(str_shuffle($numbers), 0, 1) . // 1 случайное число
            substr(str_shuffle($specialCharacters), 0, 1); // 1 случайный специальный символ

        // Перемешиваем и обрезаем до нужной длины
        return substr(str_shuffle($password), 0, max($length, 8));
    }
}
