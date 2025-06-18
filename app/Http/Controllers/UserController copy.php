<?php

namespace App\Http\Controllers;

use App\Helpers\UserHelper;
use App\Models\Employer;
use App\Models\HrJobseekers;
use Illuminate\Support\Str;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    //
    public $rights = false;


    public function __construct()
    {

        /*  $this->middleware(function ($request, $next) {
            $this->rights = Gate::check('main-admin');
            return $next($request);
        }); */

        // Проверяем аутентификацию и роли через middleware
        $this->middleware('auth');
        $this->middleware('checkRole:super-admin,client');

        /* App::setLocale($locale); */
    }



    public function dashboard(Request $request)
    {


        /*  $search = $request->input('search', '');
        $perPage = $request->input('perPage', 5);
        $currentPage = $request->input('currentPage', 1);

        // Получаем текущего аутентифицированного пользователя
        $user_auth = Auth::user();

        $query = User::query();

        // Применяем фильтрацию по полям
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('unp', 'like', "%{$search}%");
            });
        }

        // Пагинация
        $users = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'currentPage', $currentPage);

        return Inertia::render('Dashboard', [
            'rights' => $this->rights,
            'user_auth' => $user_auth,
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]); */



        // Получаем текущего аутентифицированного пользователя
        $user_auth = Auth::user();
        /* $users = User::orderByDesc('created_at')->get(); */



        /*         $query = User::query()
            ->leftJoin('recruitment_agencies', 'users.id', '=', 'recruitment_agencies.id_user')
            ->select('users.*', 'recruitment_agencies.original_email', 'recruitment_agencies.unp', 'recruitment_agencies.original_password');
 */


        $users = User::query()
            ->leftJoin('clients', 'users.id', '=', 'clients.user_id')
            ->select(
                'users.*',
                'clients.original_email',
                'clients.original_password',

            )
            ->where('users.role', '!=', 'super_admin') // Исключаем пользователей с ролью 'super_admin'
            ->orderByDesc('users.created_at'); // Убедитесь, что вы сортируете по правильному полю

        $perPage = 10; // Замените, если нужно, на 'per_page'
        $currentPage = 1;

        // Пагинация
        $users = $users->paginate($perPage, ['*'], 'page', $currentPage);


        if ($user_auth->role === 'client') {

            /* return redirect()->route('dashboard_client'); */


            // Возвращаем представление для всех ролей, так как проверка уже проведена на уровне middleware
            return Inertia::render('Client/Dashboard', [
                'rights' => $this->rights,
                'user_auth' => $user_auth,

                // Другие данные, если необходимо
            ]);
        }


        if ($user_auth->role === 'super_admin') {



            // Возвращаем представление для всех ролей, так как проверка уже проведена на уровне middleware
            return Inertia::render('Admin/Dashboard', [
                'rights' => $this->rights,
                'user_auth' => $user_auth,
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total'  => $users->total(),
                ],
                // Другие данные, если необходимо
            ]);
        }




        if ($user_auth->role === 'super_admin') {



            // Возвращаем представление для всех ролей, так как проверка уже проведена на уровне middleware
            return Inertia::render('Admin/Dashboard', [
                'rights' => $this->rights,
                'user_auth' => $user_auth,
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total'  => $users->total(),
                ],
                // Другие данные, если необходимо
            ]);
        }
    }

    //Axios - запросы из Dashboard.vue


    public function filtering_user(Request $request)
    {
        // Получение параметров запроса
        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 10);
        $currentPage = $request->input('page', 1);

        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        // Ограничение допустимых значений для сортировки
        $allowedSortFields = ['created_at', 'name', 'email', 'role']; // Разрешенные поля для сортировки
        $allowedSortOrders = ['asc', 'desc']; // Разрешенные направления сортировки

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at'; // Значение по умолчанию
        }
        if (!in_array($sortOrder, $allowedSortOrders)) {
            $sortOrder = 'desc'; // Значение по умолчанию
        }

        // Создание главного запроса
        $query = User::query()
            ->leftJoin('clients', 'users.id', '=', 'clients.user_id')
            ->select(
                'users.*',
                'clients.original_email',
                'clients.telegram',
                'clients.phone_number',
                'clients.original_password'
            )
            ->where('users.role', '!=', 'super_admin'); // Исключаем пользователей с ролью 'super_admin'

        // Если есть параметр search, добавляем фильтрацию
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('clients.telegram', 'like', "%{$search}%")
                    ->orWhere('clients.phone_number', 'like', "%{$search}%");
            });
        }

        // Применение сортировки
        $query->orderBy($sortField, $sortOrder);

        // Выполняем пагинацию
        $users = $query->paginate($perPage, ['*'], 'page', $currentPage);

        // Формируем и возвращаем ответ JSON
        return response()->json([
            'users' => $users->items(), // Преобразованный список пользователей
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }






    public function axios_add_user(Request $request)
    {
        // Валидация входящих данных
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telegram' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'role' => 'required|in:client,admin', // Указание допустимых ролей
        ]);

        // Проверка на существование пользователя с аналогичными данными
        $existingAgency = DB::table('clients')
            ->where(function ($query) use ($validatedData) {
                $query->where('original_email', $validatedData['email']);
                if (isset($validatedData['telegram'])) {
                    $query->orWhere('telegram', $validatedData['telegram']);
                }
                if (isset($validatedData['phone_number'])) {
                    $query->orWhere('phone_number', $validatedData['phone_number']);
                }
            })
            ->first();

        if ($existingAgency) {
            return response()->json([
                'message' => 'Позиция с такими данными уже существует.'
            ], 409); // Код состояния 409 Conflict
        }

        // Генерация случайного пароля
        $password_random = $this->generateUniquePassword();

        // Хеширование пароля для хранения в базе
        $hashedPassword = bcrypt($password_random);

        // Создание пользователя в таблице `users`
        $userId = DB::table('users')->insertGetId([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => $hashedPassword,
            'role' => $validatedData['role'],
            'created_at' => now(), // Установка даты создания
            'updated_at' => now(), // Установка даты обновления
        ]);

        // Создание клиента в таблице `clients`
        DB::table('clients')->insert([
            'user_id' => $userId, // Исправлено на user_id, чтобы соответствовать конвенции Laravel
            'name' => $validatedData['name'],
            'original_email' => $validatedData['email'],
            'telegram' => $validatedData['telegram'] ?? null,
            'phone_number' => $validatedData['phone_number'] ?? null,
            'original_password' => $password_random, // Сохранение исходного пароля
            'created_at' => now(), // Установка даты создания
            'updated_at' => now(), // Установка даты обновления
        ]);

        // Получение пользователя из базы данных по ID
        $user = DB::table('users')->where('id', $userId)->first();

        // Возвращение успешного ответа
        return response()->json([
            'user' => $user,
            'message' => 'Пользователь успешно создан!'
        ], 201); // Код 201 означает, что ресурс был успешно создан
    }


    /**
     * Возвращает данные пользователя для редактирования.
     */
    public function axios_edit_user(Request $request)
    {
        // Проверка наличия пользователя
        $user = DB::table('users')->where('id', $request->id)->first();

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден.'], 404);
        }

        // Поиск в связанной таблице
        $clientDetails = DB::table('clients')->where('user_id', $request->id)->first();

        // Добавляем дополнительные поля из таблицы clients, если запись существует
        if ($clientDetails) {
            $user->telegram = $clientDetails->telegram ?? null; // Telegram
            $user->phone_number = $clientDetails->phone_number ?? null; // Телефон
        } else {
            $user->telegram = null;
            $user->phone_number = null;
        }

        // Возврат успешного ответа
        return response()->json([
            'user' => $user,
            'message' => 'Данные пользователя успешно загружены.',
        ], 200); // HTTP 200 — OK
    }




    /**
     * Обновляет данные пользователя.
     */
    public function axios_update_user(Request $request, $id)
    {
        // Проверка наличия пользователя
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден.'], 404);
        }

        // Валидация входящих данных
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|string|max:50',
            'telegram' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
        ]);

        // Проверка на существование пользователя с аналогичным email, исключая текущий ID
        $existingClient = DB::table('clients')
            ->where(function ($query) use ($validatedData) {
                $query->where('original_email', $validatedData['email']);
            })
            ->where('user_id', '!=', $id)
            ->first();

        if ($existingClient) {
            return response()->json([
                'message' => 'Позиция с таким email уже существует.',
            ], 409); // HTTP 409 Conflict
        }

        // Обновление данных в таблице users
        DB::table('users')->where('id', $id)->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'role' => $validatedData['role'],
        ]);

        // Обновление данных в таблице clients
        DB::table('clients')->updateOrInsert(
            ['user_id' => $id], // Условие проверки существования записи
            [
                'name' => $validatedData['name'],
                'original_email' => $validatedData['email'],
                'telegram' => $validatedData['telegram'] ?? null,
                'phone_number' => $validatedData['phone_number'] ?? null,
            ]
        );

        // Возвращаем обновленные данные
        $updatedUser = DB::table('users')
            ->leftJoin('clients', 'users.id', '=', 'clients.user_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                'clients.telegram',
                'clients.phone_number'
            )
            ->where('users.id', $id)
            ->first();

        return response()->json([
            'user' => $updatedUser,
            'message' => 'Пользователь успешно обновлен.',
        ], 200); // HTTP 200 — OK
    }






    /**
     * Удаляет пользователя и связанные записи (recruitment_agencies).
     */
    public function axios_delete_user($id)
    {
        // Проверка, существует ли пользователь
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден.'], 404);
        }

        // Выполняем удаление в транзакции
        DB::transaction(function () use ($id) {
            // Удаляем связанные записи в таблице clients
            DB::table('clients')->where('user_id', $id)->delete();

            // Удаляем саму запись пользователя
            DB::table('users')->where('id', $id)->delete();
        });

        return response()->json(['message' => 'Пользователь и связанные данные успешно удалены.'], 200); // HTTP 200 OK
    }

    /**
     * Активирует или деактивирует пользователя.
     */
    public function axios_active_user(Request $request)
    {
        // Проверка, существует ли пользователь
        $user = DB::table('users')->where('id', $request->id)->first();

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден.'], 404);
        }

        // Обновление поля is_active
        DB::table('users')->where('id', $request->id)->update([
            'is_active' => $request->checked,
        ]);

        // Получаем обновленные данные пользователя
        $updatedUser = DB::table('users')->where('id', $request->id)->first();

        // Возврат успешного ответа
        return response()->json([
            'user' => $updatedUser,
            'message' => $request->checked ? 'Пользователь активирован.' : 'Пользователь деактивирован.',
        ], 200); // HTTP 200 OK
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
