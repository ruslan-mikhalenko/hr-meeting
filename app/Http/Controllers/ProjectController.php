<?php


namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

use Illuminate\Support\Str;

use App\Services\TelegramService;
use Carbon\Carbon;

class ProjectController extends Controller
{
    public function __construct()
    {
        // Middleware для проверки аутентификации
        $this->middleware('auth');
        $this->middleware('checkRole:super-admin,client');
    }

    /**
     * Отображение дашборда с проектами.
     */
    public function dashboard(Request $request)
    {
        $user_auth = Auth::user();



        if ($user_auth->role === 'client') {

            return redirect()->route('dashboard');
        }



        // Получение списка проектов
        $projects = DB::table('projects')
            ->select(
                'projects.*',
                'clients.name as client_name' // Получение имени клиента
            )
            ->join('clients', 'projects.user_id', '=', 'clients.user_id') // Присоединение к таблице clients
            ->orderByDesc('projects.created_at'); // Сортировка по дате создания

        $perPage = 10; // Количество записей на странице
        $currentPage = $request->input('page', 1); // Текущая страница

        // Пагинация
        $projects = $projects->paginate($perPage, ['*'], 'page', $currentPage);


        // Обработка project_link: транслитерация, удаление спецсимволов, нижний регистр
        $projects->getCollection()->transform(function ($landing) {
            $link = $landing->link ?? '';

            // Удаление префикса https://t.me/
            $link = str_replace('https://t.me/', '', $link);

            // Преобразуем: транслитерация, удаление лишнего, нижний регистр
            $link = Str::ascii($link); // Преобразование кириллицы в латиницу
            $link = preg_replace('/[^A-Za-z0-9_]/', '', $link); // Удаление спецсимволов и - и +
            $link = Str::lower($link); // Нижний регистр

            $landing->project_link_clean = $link;

            return $landing;
        });


        // Возвращаем данные на фронтенд с помощью Inertia
        return Inertia::render('Admin/ProjectsDashboard', [
            'user_auth' => $user_auth,
            'projects' => $projects->items(),
            'pagination' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    /**
     * Фильтрация проектов.
     */
    public function filtering_project(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 10);
        $currentPage = $request->input('page', 1);

        $sortField = $request->input('sortField', 'created_at'); // Поле сортировки
        $sortOrder = $request->input('sortOrder', 'desc'); // Направление сортировки

        // Допустимые значения полей для сортировки
        $allowedSortFields = ['id', 'name', 'created_at'];
        $allowedSortOrders = ['asc', 'desc'];

        // Проверяем введённые поля сортировки
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        if (!in_array($sortOrder, $allowedSortOrders)) {
            $sortOrder = 'desc';
        }

        // Формируем запрос к проектам
        $query = DB::table('projects')
            ->select('projects.*', 'clients.name as client_name') // Выгружаем данные клиента
            ->join('clients', 'projects.user_id', '=', 'clients.user_id') // Присоединяем клиентов
            ->when($search, function ($q) use ($search) {
                $q->where('projects.name', 'LIKE', "%{$search}%")
                    ->orWhere('clients.name', 'LIKE', "%{$search}%");
            })
            ->orderBy($sortField, $sortOrder);

        // Пагинация
        $projects = $query->paginate($perPage, ['*'], 'page', $currentPage);


        // Обработка project_link: транслитерация, удаление спецсимволов, нижний регистр
        $projects->getCollection()->transform(function ($landing) {
            $link = $landing->link ?? '';

            // Удаление префикса https://t.me/
            $link = str_replace('https://t.me/', '', $link);

            // Преобразуем: транслитерация, удаление лишнего, нижний регистр
            $link = Str::ascii($link); // Преобразование кириллицы в латиницу
            $link = preg_replace('/[^A-Za-z0-9_]/', '', $link); // Удаление спецсимволов и - и +
            $link = Str::lower($link); // Нижний регистр

            $landing->project_link_clean = $link;

            return $landing;
        });




        return response()->json([
            'projects' => $projects->items(),
            'pagination' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    /**
     * Добавление нового проекта.
     */
    public function axios_add_project(Request $request)
    {
        // Валидация
        $validatedData = $request->validate([
            'user_id' => 'required',
            'name' => 'required|string|max:255',
            'link' => 'required|max:255',
            'yandex_metric_id' => 'required|string|max:255',
            'goal_id' => 'required|string|max:255',
            'unsubscribe_goal_id' => 'required|string|max:255',
            'click_land_goal_id' => 'nullable|string|max:255',
            'measurement_protocol_token' => 'required|string|max:255',
            'landing_url' => 'nullable|max:255',
        ]);


        $telegramService = new TelegramService();
        // Получение информации о фото канала
        $channelInfo = $telegramService->getChannelPhoto($validatedData['link']) ?? [];

        // Получение основной информации о канале
        $channelInfoOther = $telegramService->getChannelInfo($validatedData['link']) ?? [];

        /* $channelInfo = $telegramService->getChannelPhoto($validatedData['link']); */
        /* dd($channelInfo['file_path'] . $channelInfoOther); */

        // Создание проекта
        $projectId = DB::table('projects')->insertGetId([
            'user_id' => $validatedData['user_id'],
            'name' => $validatedData['name'],
            'link' => $validatedData['link'],
            'yandex_metric_id' => $validatedData['yandex_metric_id'],
            'goal_id' => $validatedData['goal_id'],
            'unsubscribe_goal_id' => $validatedData['unsubscribe_goal_id'],
            'click_land_goal_id' => $validatedData['click_land_goal_id'],

            'measurement_protocol_token' => $validatedData['measurement_protocol_token'],
            'landing_url' => $validatedData['landing_url'],

            'channel_id' => $channelInfoOther['id'] ?? null,
            'title' => $channelInfoOther['title'] ?? null,
            'photo' => $channelInfo['file_path'] ?? null,
            'username' => $channelInfoOther['username'] ?? null,
            'participants_count' => $channelInfoOther['participants_count'] ?? null,
            'about' => $channelInfoOther['about'] ?? null,
            'type' => $channelInfoOther['type'] ?? null,
            'privacy' => $channelInfoOther['privacy'] ?? null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $project = DB::table('projects')->where('id', $projectId)->first();

        return response()->json([
            'project' => $project,
            'message' => 'Проект успешно создан!',
        ], 201);
    }



    /**
     * Возвращает данные проекта для редактирования.
     */
    public function axios_edit_project(Request $request)
    {


        // Выполнение запроса с `join`, чтобы получить данные из связанных таблиц
        $project = DB::table('projects')
            ->leftJoin('clients', 'projects.user_id', '=', 'clients.user_id') // Соединение таблиц
            ->select('projects.*', 'clients.name as client') // Выбираем все поля из `payments` и поле `name` из `recruitment_agencies`
            ->where('projects.id', $request->id) // Фильтр платежа по ID
            ->first(); // Получаем первую запись


        // Проверка наличия проекта

        if (!$project) {
            return response()->json(['message' => 'Проект не найден.'], 404);
        }

        // Возврат успешного ответа
        return response()->json([
            'project' => $project,
            'message' => 'Данные проекта успешно загружены.',
        ], 200); // HTTP 200 — OK
    }

    /**
     * Обновляет данные проекта.
     */
    public function axios_update_project(Request $request, $id)
    {
        // Проверка наличия проекта
        $project = DB::table('projects')->where('id', $id)->first();

        if (!$project) {
            return response()->json(['message' => 'Проект не найден.'], 404);
        }

        // Валидация входящих данных
        $validatedData = $request->validate([
            'user_id' => 'required|exists:clients,user_id', // user_id должен существовать в таблице clients
            'name' => 'required|string|max:255',
            'link' => 'required|max:255',
            'yandex_metric_id' => 'required|string|max:255',
            'goal_id' => 'required|string|max:255',
            'unsubscribe_goal_id' => 'required|string|max:255',
            'click_land_goal_id' => 'nullable|string|max:255',
            'measurement_protocol_token' => 'required|string|max:255',
            'landing_url' => 'nullable|max:255',
        ]);



        // Удаление старого фото, если оно существует
        if ($project->photo) {
            // Извлекаем относительный путь к файлу (убираем домен или хост)
            $filePath = parse_url($project->photo, PHP_URL_PATH); // Получаем "/channel_photos/5438270552516180007_c_2.jpg"

            // Удаляем файл, если он существует
            if (file_exists(public_path($filePath))) {
                unlink(public_path($filePath)); // Удаление файла
            }
        }

        $telegramService = new TelegramService();

        /* dd($validatedData['link']); */
        // Получение информации о фото канала
        $channelInfo = $telegramService->getChannelPhoto($validatedData['link']) ?? [];


        // Получение основной информации о канале
        $channelInfoOther = $telegramService->getChannelInfo($validatedData['link']) ?? [];


        // Обновление данных в таблице projects
        DB::table('projects')->where('id', $id)->update([
            'user_id' => $validatedData['user_id'],
            'name' => $validatedData['name'],
            'link' => $validatedData['link'],
            'yandex_metric_id' => $validatedData['yandex_metric_id'],
            'goal_id' => $validatedData['goal_id'],
            'unsubscribe_goal_id' => $validatedData['unsubscribe_goal_id'],
            'click_land_goal_id' => $validatedData['click_land_goal_id'],
            'measurement_protocol_token' => $validatedData['measurement_protocol_token'],
            'landing_url' => $validatedData['landing_url'],

            'channel_id' => $channelInfoOther['id'] ?? null,
            'title' => $channelInfoOther['title'] ?? null,
            'photo' => $channelInfo['file_path'] ?? null,
            'username' => $channelInfoOther['username'] ?? null,
            /* 'participants_count' => $channelInfoOther['participants_count'] ?? null, */
            'about' => $channelInfoOther['about'] ?? null,
            'type' => $channelInfoOther['type'] ?? null,
            'privacy' => $channelInfoOther['privacy'] ?? null,

            'updated_at' => now(),
        ]);

        // Возвращаем обновлённые данные
        $updatedProject = DB::table('projects')->where('id', $id)->first();

        return response()->json([
            'project' => $updatedProject,
            'message' => 'Проект успешно обновлен.',
        ], 200); // HTTP 200 — OK
    }

    /**
     * Удаляет проект.
     */
    public function axios_delete_project($id)
    {
        // Проверка, существует ли проект
        $project = DB::table('projects')->where('id', $id)->first();

        if (!$project) {
            return response()->json(['message' => 'Проект не найден.'], 404);
        }

        // Удаление проекта
        DB::table('projects')->where('id', $id)->delete();

        return response()->json(['message' => 'Проект успешно удалён.'], 200); // HTTP 200 OK
    }

    /**
     * Активирует или деактивирует проект.
     */
    public function axios_active_project(Request $request)
    {
        // Проверка, существует ли проект
        $project = DB::table('projects')->where('id', $request->id)->first();

        if (!$project) {
            return response()->json(['message' => 'Проект не найден.'], 404);
        }

        // Обновление поля is_active
        DB::table('projects')->where('id', $request->id)->update([
            'is_active' => $request->checked,
        ]);

        // Получаем обновлённые данные проекта
        $updatedProject = DB::table('projects')->where('id', $request->id)->first();

        // Возврат успешного ответа
        return response()->json([
            'project' => $updatedProject,
            'message' => $request->checked ? 'Проект активирован.' : 'Проект деактивирован.',
        ], 200); // HTTP 200 OK
    }


    public function projects_search(Request $request)
    {


        $query = $request->input('query'); // Строка поиска
        /*   $id_hr = $request->input('id_hr');  */ // Дополнительный ID HR для фильтрации

        // Строим запрос с соединением между таблицами
        $results = Project::query()
            ->where(function ($q) use ($query) {
                // Если указан запрос, фильтруем по id_user и имени
                if ($query) {
                    $q->where('projects.user_id', 'like', '%' . $query . '%') // Поиск по id_user
                        ->orWhere('projects.name', 'like', '%' . $query . '%'); // Поиск по имени
                }
            })
            ->select('projects.*') // Выбираем нужные поля
            ->get();


        // Возвращаем результаты в формате JSON
        return response()->json($results);
    }





    /** -------------------------------------------------------------------------------------------- */

    public function project($id)
    {
        $user_auth = Auth::user();

        // Объединяем проекты с таблицей пользователей (по user_id)
        $project = DB::table('projects')
            ->join('users', 'projects.user_id', '=', 'users.id') // Объединение с таблицей users
            ->where('projects.user_id', $user_auth->id) // Фильтр по ID проекта
            ->where('projects.id', $id) // Фильтр по ID проекта
            ->select(
                'projects.*', // Все поля из таблицы projects
                'users.is_active as user_active', // Активность клиента
            )
            ->first();

        if (!$project) {
            return redirect('/'); // Перенаправление на главную
        }

        /* dd($subscriber->photo); */

        $subscribers = DB::table('subscribers')
            ->where('project_id', $id)
            // ->where('is_active', 1) // если нужно, раскомментируй
            ->paginate(10); // paginate сразу делает запрос и возвращает постраничные данные

        if ($subscribers->isEmpty()) {
            Log::info("Нет подписчиков для проекта с ID {$id}");
        }

        /** Лендинги */

        $landings = DB::table('landings')
            ->where('project_id', $id)
            ->where('is_active', 1) // если нужно, раскомментируй
            ->paginate(10); // paginate сразу делает запрос и возвращает постраничные данные

        if ($landings->isEmpty()) {
            Log::info("Нет лендингов для проекта с ID {$id}");
        }


        /*  dd($subscribers); */


        return Inertia::render(
            'Client/Project',
            [
                'project' => $project,
                'user_auth' => $user_auth,
                'subscribers' => $subscribers,
                'landings' => $landings
            ],
        );

        // Получение списка проектов
        /* $projects = DB::table('projects')
            ->select(
                'projects.*',
                'clients.name as client_name' // Получение имени клиента
            )
            ->join('clients', 'projects.user_id', '=', 'clients.user_id') // Присоединение к таблице clients
            ->orderByDesc('projects.created_at'); // Сортировка по дате создания

        $perPage = 10; // Количество записей на странице
        $currentPage = $request->input('page', 1); // Текущая страница

        // Пагинация
        $projects = $projects->paginate($perPage, ['*'], 'page', $currentPage);

        // Возвращаем данные на фронтенд с помощью Inertia
        return Inertia::render('Admin/ProjectsDashboard', [
            'user_auth' => $user_auth,
            'projects' => $projects->items(),
            'pagination' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]); */
    }


    public function getChartData(Request $request, $id)
    {
        $grouping = $request->input('grouping', 'day');

        /* $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::parse('2025-06-02')->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay(); */


        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // Получаем первую дату подписчика, если startDate не передан
        if (!$startDate) {
            $firstDate = DB::table('subscribers')
                ->where('project_id', $id)
                ->orderBy('created_at', 'asc')
                ->value('created_at');

            $startDate = $firstDate ? Carbon::parse($firstDate)->startOfDay() : Carbon::now()->startOfDay();
        } else {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        // Обработка endDate как раньше
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        switch ($grouping) {
            case 'hour':
                $format = '%Y-%m-%d %H:00:00';
                $phpFormat = 'Y-m-d H:00:00';
                $step = 'addHour';
                break;
            case 'day':
                $format = '%Y-%m-%d';
                $phpFormat = 'Y-m-d';
                $step = 'addDay';
                break;
            case 'month':
                $format = '%Y-%m';
                $phpFormat = 'Y-m';
                $step = 'addMonth';
                break;
            case 'year':
                $format = '%Y';
                $phpFormat = 'Y';
                $step = 'addYear';
                break;
            default:
                $format = '%Y-%m-%d';
                $phpFormat = 'Y-m-d';
                $step = 'addDay';
                break;
        }

        $periods = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->$step()) {
            $periods[] = $date->format($phpFormat);
        }


        /* dd($startDate . '   ' . $endDate); */

        $is_active = $request->input('is_active', 1); // по умолчанию подписчики

        /*         $chartData = DB::table('subscribers')
            ->selectRaw("DATE_FORMAT(updated_at, '{$format}') as period, COUNT(*) as count")
            ->where('project_id', $id)
            ->where('is_active', $is_active) // ← 🔹 ключевая строка
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period'); */

        /**Приме для каждого project_id свой диапазон даты исключения - чтобы первый парсинг был исключен как первые подписчики */
        /* $chartData = DB::table('subscribers')
            ->selectRaw("DATE_FORMAT(updated_at, '{$format}') as period, COUNT(*) as count")
            ->where('project_id', $id)
            ->where('is_active', $is_active)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->when($id == 123, function ($query) {
                $query->whereNotBetween('updated_at', ['2025-06-02 14:01:00', '2025-06-02 14:02:10']);
            })
            ->when($id == 456, function ($query) {
                $query->whereNotBetween('updated_at', ['2025-06-01 12:00:00', '2025-06-01 12:01:30']);
            })
            ->when($id == 789, function ($query) {
                $query->whereNotBetween('updated_at', ['2025-06-03 09:15:00', '2025-06-03 09:16:00']);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period'); */


        $chartData = DB::table('subscribers')
            ->selectRaw("DATE_FORMAT(updated_at, '{$format}') as period, COUNT(*) as count")
            ->where('project_id', $id)
            ->where('is_active', $is_active)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->whereNotBetween('updated_at', ['2025-06-12 22:36:55', '2025-06-12 22:39:55']) // 🔥 исключаем участок - первые по парсингу из канала (не причисляем их к подписчикам)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');




        $total = 0;
        $result = [];

        foreach ($periods as $period) {
            $count = isset($chartData[$period]) ? $chartData[$period]->count : 0;
            $total += $count; // Считаем общее количество

            $result[] = [
                'period' => $period,
                'count' => $count,
            ];
        }

        return response()->json([
            'chartData' => $result,
            'total' => $total,
        ]);
    }



    public function getCumulativeChart(Request $request, $id)
    {
        $grouping = $request->input('grouping', 'day');

        /* $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::parse('2025-06-02')->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

       */
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // Получаем первую дату подписчика, если startDate не передан
        if (!$startDate) {
            $firstDate = DB::table('subscribers')
                ->where('project_id', $id)
                ->orderBy('created_at', 'asc')
                ->value('created_at');

            $startDate = $firstDate ? Carbon::parse($firstDate)->startOfDay() : Carbon::now()->startOfDay();
        } else {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        // Обработка endDate как раньше
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();



        switch ($grouping) {
            case 'hour':
                $format = '%Y-%m-%d %H:00:00';
                $phpFormat = 'Y-m-d H:00:00';
                $step = 'addHour';
                break;
            case 'day':
                $format = '%Y-%m-%d';
                $phpFormat = 'Y-m-d';
                $step = 'addDay';
                break;
            case 'month':
                $format = '%Y-%m';
                $phpFormat = 'Y-m';
                $step = 'addMonth';
                break;
            case 'year':
                $format = '%Y';
                $phpFormat = 'Y';
                $step = 'addYear';
                break;
            default:
                $format = '%Y-%m-%d';
                $phpFormat = 'Y-m-d';
                $step = 'addDay';
                break;
        }

        $periods = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->$step()) {
            $periods[] = $date->format($phpFormat);
        }

        // Получаем подписки и отписки
        $subscriptions = DB::table('subscribers')
            ->selectRaw("DATE_FORMAT(updated_at, '{$format}') as period, COUNT(*) as count")
            ->where('project_id', $id)
            ->where('is_active', 1)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->whereNotBetween('updated_at', ['2025-06-12 22:36:55', '2025-06-12 22:39:55'])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $unsubscriptions = DB::table('subscribers')
            ->selectRaw("DATE_FORMAT(updated_at, '{$format}') as period, COUNT(*) as count")
            ->where('project_id', $id)
            ->where('is_active', 0)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $chartData = [];
        $cumulativeData = [];
        $tableData = [];  // Вот сюда пойдут данные для таблицы
        $total = 0;
        $cumulative = 0;

        foreach ($periods as $period) {
            $subs = $subscriptions[$period]->count ?? 0;
            $unsubs = $unsubscriptions[$period]->count ?? 0;
            $net = $subs - $unsubs;

            $total += $subs;
            $cumulative += $net;

            $chartData[] = [
                'period' => $period,
                'count' => $subs,
            ];

            $cumulativeData[] = [
                'period' => $period,
                'count' => $cumulative,
            ];

            $tableData[] = [
                'period' => $period,
                'subscriptions' => $subs,
                'unsubscriptions' => $unsubs,
                'result' => $net, // 👈 новая строка
                'cumulative' => $cumulative,
            ];
        }

        return response()->json([
            'chartData' => $chartData,
            'cumulativeChart' => $cumulativeData,
            'tableData' => $tableData,  // Добавил сюда
            'total' => $total,
        ]);
    }
}
