<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct()
    {
        // Middleware для проверки аутентификации
        $this->middleware('auth');
        $this->middleware('checkRole:super-admin,manager');
    }

    /**
     * Отображение дашборда с проектами.
     */
    public function dashboard(Request $request)
    {
        $user_auth = Auth::user();

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
                    ->orWhere('landing_url', 'LIKE', "%{$search}%");
            })
            ->orderBy($sortField, $sortOrder);

        // Пагинация
        $projects = $query->paginate($perPage, ['*'], 'page', $currentPage);

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
            'link' => 'nullable|max:255',
            'yandex_metric_id' => 'nullable|string|max:255',
            'goal_id' => 'nullable|string|max:255',
            'measurement_protocol_token' => 'nullable|string|max:255',
            'landing_url' => 'nullable|url|max:255',
        ]);

        // Создание проекта
        $projectId = DB::table('projects')->insertGetId([
            'user_id' => $validatedData['user_id'],
            'name' => $validatedData['name'],
            'link' => $validatedData['link'],
            'yandex_metric_id' => $validatedData['yandex_metric_id'],
            'goal_id' => $validatedData['goal_id'],
            'measurement_protocol_token' => $validatedData['measurement_protocol_token'],
            'landing_url' => $validatedData['landing_url'],
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
        // Проверка наличия проекта
        $project = DB::table('projects')->where('id', $request->id)->first();

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
            'link' => 'nullable|url|max:255',
            'yandex_metric_id' => 'nullable|string|max:255',
            'goal_id' => 'nullable|string|max:255',
            'measurement_protocol_token' => 'nullable|string|max:255',
            'landing_url' => 'nullable|url|max:255',
        ]);

        // Обновление данных в таблице projects
        DB::table('projects')->where('id', $id)->update([
            'user_id' => $validatedData['user_id'],
            'name' => $validatedData['name'],
            'link' => $validatedData['link'],
            'yandex_metric_id' => $validatedData['yandex_metric_id'],
            'goal_id' => $validatedData['goal_id'],
            'measurement_protocol_token' => $validatedData['measurement_protocol_token'],
            'landing_url' => $validatedData['landing_url'],
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
}
