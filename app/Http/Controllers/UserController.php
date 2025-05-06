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

            return redirect()->route('dashboard_client');
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



        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 10); // Замените, если нужно, на 'per_page'
        $currentPage = $request->input('page', 1); // Убедитесь, что передаётся текущая страница

        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        $query = User::query()
            ->leftJoin('clients', 'users.id', '=', 'clients.user_id')
            ->select(
                'users.*',
                'clients.original_email',
                'clients.unp',
                'clients.original_password',

            )
            ->where('users.role', '!=', 'super_admin'); // Исключаем пользователей с ролью 'super_admin'

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        // Применение сортировки
        if ($sortField && $sortOrder) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderByDesc('created_at');
        }

        // Выполняем пагинацию
        $users = $query->paginate($perPage, ['*'], 'page', $currentPage);




        /* dd($users); */

        // Формируем ответ JSON
        return response()->json([
            'users' => $users->items(), // Преобразованные пользователи
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


        /* dd($request->all()); */

        // Валидация входящих данных
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'unp' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required',
            /* 'password' => 'required|string|min:8', */ // Можно добавить другие правила по необходимости
        ]);



        // Проверка на существование пользователя с аналогичным email и unp
        $existingAgency = DB::table('recruitment_agencies')
            ->where('original_email', $validatedData['email'])
            ->orWhere('unp', $validatedData['unp'])
            ->first();

        if ($existingAgency) {
            return response()->json([
                'message' => 'Позиция с таким email или ИНН уже существует.'
            ], 409); // Код состояния 409 Conflict
        }



        // Создание нового пользователя или обновление существующего
        /*         $user = new User; // Или $user = User::find($request->user_id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password); // Хеширование пароля
        $user->save(); */


        $password_random = $this->generateUniquePassword();

        $hashedPassword = bcrypt($password_random);



        $userId = DB::table('users')->insertGetId([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => $hashedPassword,
            'role' => $validatedData['role'],
            'created_at' => now(), // Установка даты создания
            'updated_at' => now(), // Установка даты обновления
        ]);







        DB::table('recruitment_agencies')->insert([
            'id_user' => $userId,
            'name' => $validatedData['name'],
            'original_email' => $validatedData['email'],
            'unp' => $validatedData['unp'],
            'original_password' => $password_random,
            'created_at' => now(), // Установка даты создания
            'updated_at' => now(), // Установка даты обновления
        ]);


        // Получение пользователя из базы данных по ID
        $user = DB::table('users')->where('id', $userId)->first();

        // Возврат успешного ответа
        return response()->json([
            'user' => $user,
            'message' => 'Пользователь успешно создан!'
        ], 201); // Код 201 означает, что ресурс был успешно создан
    }


    public function axios_edit_user(Request $request)
    {


        // Получение пользователя из базы данных по ID
        $user = DB::table('users')->where('id', $request['id'])->first();

        $recruitment_agencies = DB::table('recruitment_agencies')->where('id_user', $request['id'])->first();

        $user->unp = $recruitment_agencies->unp;

        // Возврат успешного ответа
        return response()->json([
            'user' => $user,
            'message' => 'Пользователь успешно создан!'
        ], 201); // Код 201 означает, что ресурс был успешно создан
    }



    public function axios_update_user(Request $request, $id)
    {

        // Проверяем, существует ли пользователь
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден.'], 404);
        }

        // Валидация входящих данных
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'unp' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required',
        ]);


        // Проверка на существование пользователя с аналогичным email или unp, исключая текущий ID
        $existingAgency = DB::table('recruitment_agencies')
            ->where(function ($query) use ($validatedData) {
                $query->where('original_email', $validatedData['email'])
                    ->orWhere('unp', $validatedData['unp']);
            })
            ->where('id_user', '!=', $id) // Добавляем условие для исключения текущего ID
            ->first(); // Выполняем запрос и получаем первую запись

        if ($existingAgency) {
            return response()->json([
                'message' => 'Позиция с таким email или ИНН уже существует.'
            ], 409); // Код состояния 409 Conflict
        }







        // Обновление данных пользователя
        DB::table('users')->where('id', $id)->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'role' => $validatedData['role'],
        ]);

        // Обновление данных пользователя
        DB::table('recruitment_agencies')->where('id_user', $id)->update([
            'name' => $validatedData['name'],
            'unp' => $validatedData['unp'],
            'original_email' => $validatedData['email'],

        ]);



        // Возвращаем обновленного пользователя
        $updatedUser = DB::table('users')->where('id', $id)->first();

        // Возврат успешного ответа
        return response()->json([
            'user' => $updatedUser,
            'message' => 'Пользователь успешно обновлён!'
        ], 201); // Код 201 означает, что ресурс был успешно создан
    }





    public function axios_delete_user($id)
    {
        // Начинаем транзакцию
        DB::transaction(function () use ($id) {
            // Сначала удаляем связанные записи
            DB::table('recruitment_agencies')->where('id_user', $id)->delete();

            // Затем удаляем пользователя
            DB::table('users')->where('id', $id)->delete();
        });

        return response()->json(['message' => 'Пользователь и связанные записи успешно удалены.']);
    }



    public function axios_active_user(Request $request)
    {
        // Обновление данных пользователя
        DB::table('users')->where('id', $request->id)->update([
            'is_active' => $request->checked

        ]);

        // Возвращаем обновленного пользователя
        $updatedUser = DB::table('users')->where('id', $request->id)->first();

        // Возврат успешного ответа
        return response()->json([
            'user' => $updatedUser,
            'message' => 'Участник успешно обновлён!'
        ], 201); // Код 201 означает, что ресурс был успешно создан
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


    /* Vacancy */

    public function vacancy()
    {

        $user_auth = Auth::user();

        if ($user_auth->role === 'hr') {

            return redirect()->route('dashboard_hr');
        }

        if ($user_auth->role === 'employer') {

            return redirect()->route('dashboard_employer');
        }

        $perPage = 10; // Замените, если нужно, на 'per_page'
        $currentPage = 1;
        $vacancys = HrJobseekers::orderByDesc('created_at');


        // Начинаем запрос
        $vacancys = HrJobseekers::query()
            ->leftJoin('recruitment_agencies', 'hr_jobseekers.id_employer', '=', 'recruitment_agencies.id_user')
            ->leftJoin('employers', 'hr_jobseekers.id_vacancy', '=', 'employers.id')
            ->leftJoin('professions', 'employers.id_profession', '=', 'professions.id') // Добавляем соединение с таблицей professions
            ->select(
                'hr_jobseekers.*',
                'recruitment_agencies.name AS employer_name',
                'recruitment_agencies.unp AS unp',
                'recruitment_agencies.original_email AS email',
                'employers.profession AS profession',
                'employers.status_job AS status_job',
                'professions.name AS profession_name' // Добавляем поле name из таблицы professions
            )
            // Используем замыкание для группировки условий
            ->where(function ($query) {
                $query->where('hr_jobseekers.status', 'Оформление')
                    ->orWhere('hr_jobseekers.status', 'Трудоустроен');
            })
            ->orderByDesc('hr_jobseekers.created_at'); // Убедитесь, что orderBy вызывается правильно




        // Пагинация
        $vacancys = $vacancys->paginate($perPage, ['*'], 'page', $currentPage);

        /* dd($vacancys->items()); */



        return Inertia::render('Admin/Vacancy', [

            'user_auth' => $user_auth,
            'vacancys' =>      $vacancys->items(),
            'pagination' => [
                'current_page' => $vacancys->currentPage(),
                'last_page' => $vacancys->lastPage(),
                'per_page' => $vacancys->perPage(),
                'total' => $vacancys->total(),
            ],

            // Другие данные, если необходимо
        ]);
    }


    public function filtering_vacancy(Request $request)
    {

        $sign = $request->sign;

        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 10); // Замените, если нужно, на 'per_page'
        $currentPage = $request->input('page', 1); // Измените на 'page' при необходимости

        $sortField = $request->input('sortField', 'created_at'); // Удалите 'created_at' по умолчанию
        $sortOrder = $request->input('sortOrder', 'desc'); // Удалите 'desc' по умолчанию

        // Начинаем запрос
        $query = HrJobseekers::query()
            ->leftJoin('recruitment_agencies AS employer_agency', 'hr_jobseekers.id_employer', '=', 'employer_agency.id_user')
            ->leftJoin('recruitment_agencies AS hr_agency', 'hr_jobseekers.id_hr', '=', 'hr_agency.id_user')
            ->leftJoin('employers', 'hr_jobseekers.id_vacancy', '=', 'employers.id')
            ->leftJoin('professions', 'employers.id_profession', '=', 'professions.id') // Добавляем соединение с таблицей professions
            ->select(
                'hr_jobseekers.*',
                'employer_agency.name AS employer_name', // Наименование работодателя
                'hr_agency.name AS hr_name',
                'employer_agency.unp AS unp',
                'employer_agency.original_email AS email',
                'employers.status_job AS status_job',
                'employers.vacancy_position AS vacancy_position',
                'employers.profession AS profession',
                'professions.name AS profession_name',
                'employers.created_at AS created_at_employer',
            );
        /* ->where(function ($query) {
                $query->where('hr_jobseekers.status', 'Оформление')
                    ->orWhere('hr_jobseekers.status', 'Трудоустроен');
            }); */

        // Получение подсчетов по вакансиям
        $countVacancyAll = HrJobseekers::count();





        $countVacancyOpen =
            HrJobseekers::query()
            ->leftJoin('employers', 'hr_jobseekers.id_vacancy', '=', 'employers.id')
            ->where('employers.status_job', 'Открыта')
            ->count('hr_jobseekers.id'); // Указывается поле для подсчета



        // Подсчет количества закрытых вакансий
        $countVacancyClosed = HrJobseekers::query()
            ->leftJoin('employers', 'hr_jobseekers.id_vacancy', '=', 'employers.id')
            ->where('employers.status_job', 'Закрыта')
            ->count('hr_jobseekers.id'); // Подсчитываем записи по id




        // Проверка значения $sign
        if ($sign == 1) {
            $query->where(function ($query) {
                $query->where('status_job', 'Закрыта');
                /*  ->orWhere('hr_jobseekers.status', 'Трудоустроен'); */
            });
        }

        // Проверка значения $sign
        if ($sign == 2) {
            // Исключаем статусы "Оформление" и "Трудоустроен"
            $query->where(function ($query) {
                /* $query->where('hr_jobseekers.status', '<>', 'Оформление') // Не равно "Оформление"
                    ->where('hr_jobseekers.status', '<>', 'Трудоустроен') // Не равно "Трудоустроен" */
                $query->where('status_job', 'Открыта');
            });
        }


        // Проверка значения $sign
        if ($sign == 3) {
            // Исключаем статусы "Оформление" и "Трудоустроен"
            $query->where(function ($query) {
                $query->where('hr_jobseekers.status', 'Рассмотрение'); // Не равно "Оформление"
                /* ->where('employers.status_job', 'Открыта'); */
            });
        }

        // Проверка значения $sign
        if ($sign == 4) {
            // Исключаем статусы "Оформление" и "Трудоустроен"
            $query->where(function ($query) {
                $query->where('hr_jobseekers.status', 'Отклонён'); // Не равно "Оформление"
                /*   ->where('employers.status_job', 'Открыта'); */
            });
        }


        // Проверка значения $sign
        if ($sign == 5) {
            // Исключаем статусы "Оформление" и "Трудоустроен"
            $query->where(function ($query) {
                $query->where('hr_jobseekers.status', 'Собеседование'); // Не равно "Оформление"
                /*  ->where('employers.status_job', 'Открыта'); */
            });
        }



        // Проверка значения $sign
        if ($sign == 6) {
            // Исключаем статусы "Оформление" и "Трудоустроен"
            $query->where(function ($query) {
                $query->where('hr_jobseekers.status', 'Оформление'); // Не равно "Оформление"
                /* ->where('employers.status_job', 'Закрыта'); */
            });
        }


        // Проверка значения $sign
        if ($sign == 7) {
            // Исключаем статусы "Оформление" и "Трудоустроен"
            $query->where(function ($query) {
                $query->where('hr_jobseekers.status', 'Трудоустроен'); // Не равно "Оформление"
                /* ->where('employers.status_job', 'Закрыта'); */
            });
        }


        // Проверка значения $sign
        if ($sign == 8) {
            // Исключаем статусы "Оформление" и "Трудоустроен"
            $query->where(function ($query) {
                $query->where('hr_jobseekers.status', 'Уволен'); // Не равно "Оформление"
                /* ->where('employers.status_job', 'Открыта'); */
            });
        }



        // Фильтрация по поисковому запросу
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('hr_jobseekers.first_name', 'like', "%{$search}%") // Указание таблицы для first_name
                    ->orWhere('hr_jobseekers.last_name', 'like', "%{$search}%") // Указание таблицы для last_name
                    ->orWhere('employer_agency.name', 'like', "%{$search}%")
                    ->orWhere('hr_agency.name', 'like', "%{$search}%");
            });
        }

        // Применение сортировки
        if ($sortField && $sortOrder) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            // Сортировка по умолчанию
            $query->orderByDesc('created_at');
        }



        // Получение подсчетов по статусам
        $countConsideration = HrJobseekers::where('status', 'Рассмотрение')->count();
        $countRejected = HrJobseekers::where('status', 'Отклонён')->count();
        $countInterview = HrJobseekers::where('status', 'Собеседование')->count();


        $countOformlenie = HrJobseekers::where('status', 'Оформление')->count();
        $countTrudoustroen = HrJobseekers::where('status', 'Трудоустроен')->count();
        $countDismissed = HrJobseekers::where('status', 'Уволен')->count();


        // Пагинация
        $vacancys = $query->paginate($perPage, ['*'], 'page', $currentPage);


        /* dd($vacancys); */

        return response()->json([
            'vacancys' => $vacancys->items(),
            'pagination' => [
                'current_page' => $vacancys->currentPage(),
                'last_page' => $vacancys->lastPage(),
                'per_page' => $vacancys->perPage(),
                'total' => $vacancys->total(),
            ],
            'counts' => [
                'countVacancyAll' => $countVacancyAll,
                'countVacancyOpen' => $countVacancyOpen,
                'countVacancyClosed' => $countVacancyClosed,
                'countConsideration' => $countConsideration,
                'countRejected' => $countRejected,
                'countInterview' => $countInterview,
                'countTrudoustroen' => $countTrudoustroen,
                'countOformlenie' => $countOformlenie,
                'countDismissed' => $countDismissed,

            ],
        ]);
    }
}
