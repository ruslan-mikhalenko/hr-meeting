<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkRole:super-admin,client');
    }

    /**
     * Отображение дашборда с лендингами.
     */
    public function dashboard(Request $request)
    {
        $user_auth = Auth::user();

        $landings = DB::table('landings')
            ->select('landings.*', 'projects.name as project_name')
            ->join('projects', 'landings.project_id', '=', 'projects.id')
            ->orderByDesc('landings.created_at');

        $perPage = 10;
        $currentPage = $request->input('page', 1);

        $landings = $landings->paginate($perPage, ['*'], 'page', $currentPage);

        return Inertia::render('Admin/LandingsDashboard', [
            'user_auth' => $user_auth,
            'landings' => $landings->items(),
            'pagination' => [
                'current_page' => $landings->currentPage(),
                'last_page' => $landings->lastPage(),
                'per_page' => $landings->perPage(),
                'total' => $landings->total(),
            ],
        ]);
    }

    /**
     * Фильтрация лендингов.
     */
    public function filtering_landing(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 10);
        $currentPage = $request->input('page', 1);
        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        $allowedSortFields = ['id', 'name', 'created_at'];
        $allowedSortOrders = ['asc', 'desc'];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        if (!in_array($sortOrder, $allowedSortOrders)) {
            $sortOrder = 'desc';
        }

        $query = DB::table('landings')
            ->select('landings.*', 'projects.name as project_name')
            ->join('projects', 'landings.project_id', '=', 'projects.id')
            ->when($search, function ($q) use ($search) {
                $q->where('landings.name', 'LIKE', "%{$search}%")
                    ->orWhere('landings.url', 'LIKE', "%{$search}%")
                    ->orWhere('landings.short_description', 'LIKE', "%{$search}%");
            })
            ->orderBy($sortField, $sortOrder);

        $landings = $query->paginate($perPage, ['*'], 'page', $currentPage);

        return response()->json([
            'landings' => $landings->items(),
            'pagination' => [
                'current_page' => $landings->currentPage(),
                'last_page' => $landings->lastPage(),
                'per_page' => $landings->perPage(),
                'total' => $landings->total(),
            ],
        ]);
    }

    /**
     * Добавление нового лендинга.
     */
    public function axios_add_landing(Request $request)
    {
        $validatedData = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'goal_click_id' => 'nullable|string|max:255',
            'goal_subscribe_id' => 'nullable|string|max:255',
        ]);

        $projectId = $validatedData['project_id'];
        $clickId = $validatedData['goal_click_id'];
        $subscribeId = $validatedData['goal_subscribe_id'];
        $url = $validatedData['url'];

        // Проверка: одинаковые цели
        if ($clickId && $subscribeId && $clickId === $subscribeId) {
            return response()->json([
                'errors' => ['goal_subscribe_id' => ['Цели не должны совпадать']],
            ], 422);
        }

        // Проверка: такая цель уже есть у другого лендинга в рамках проекта
        $existingGoalClick = $clickId
            ? DB::table('landings')
            ->where('project_id', $projectId)
            ->where('goal_click_id', $clickId)
            ->exists()
            : false;

        if ($existingGoalClick) {
            return response()->json([
                'errors' => ['goal_click_id' => ['Такая цель перехода уже используется в проекте']],
            ], 422);
        }

        $existingGoalSubscribe = $subscribeId
            ? DB::table('landings')
            ->where('project_id', $projectId)
            ->where('goal_subscribe_id', $subscribeId)
            ->exists()
            : false;

        if ($existingGoalSubscribe) {
            return response()->json([
                'errors' => ['goal_subscribe_id' => ['Такая цель подписки уже используется в проекте']],
            ], 422);
        }

        // Проверка: такой URL уже есть в этом проекте
        $existingUrl = DB::table('landings')
            ->where('project_id', $projectId)
            ->where('url', $url)
            ->exists();

        if ($existingUrl) {
            return response()->json([
                'errors' => ['url' => ['Такой URL уже используется в проекте']],
            ], 422);
        }

        // Вставка
        $landingId = DB::table('landings')->insertGetId([
            'project_id' => $projectId,
            'name' => $validatedData['name'],
            'url' => $url,
            'description' => $validatedData['description'] ?? null,
            'short_description' => $validatedData['short_description'] ?? null,
            'goal_click_id' => $clickId,
            'goal_subscribe_id' => $subscribeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $landing = DB::table('landings')->where('id', $landingId)->first();

        return response()->json([
            'landing' => $landing,
            'message' => 'Лендинг успешно создан!',
        ], 201);
    }

    /**
     * Возвращает данные лендинга для редактирования.
     */
    public function axios_edit_landing(Request $request)
    {
        $landing = DB::table('landings')
            ->join('projects', 'landings.project_id', '=', 'projects.id')
            ->select('landings.*', 'projects.name as project')
            ->where('landings.id', $request->id)
            ->first();

        if (!$landing) {
            return response()->json(['message' => 'Лендинг не найден.'], 404);
        }

        return response()->json([
            'landing' => $landing,
            'message' => 'Данные лендинга успешно загружены.',
        ], 200);
    }

    /**
     * Обновляет данные лендинга.
     */
    public function axios_update_landing(Request $request, $id)
    {
        $landing = DB::table('landings')->where('id', $id)->first();

        if (!$landing) {
            return response()->json(['message' => 'Лендинг не найден.'], 404);
        }

        $validatedData = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'goal_click_id' => 'nullable|string|max:255',
            'goal_subscribe_id' => 'nullable|string|max:255',
        ]);

        $projectId = $validatedData['project_id'];
        $clickId = $validatedData['goal_click_id'];
        $subscribeId = $validatedData['goal_subscribe_id'];
        $url = $validatedData['url'];

        // ❗ Проверка: одинаковые цели
        if ($clickId && $subscribeId && $clickId === $subscribeId) {
            return response()->json([
                'errors' => ['goal_subscribe_id' => ['Цели не должны совпадать']],
            ], 422);
        }

        // ❗ Проверка: цель перехода уже есть у другого лендинга
        $existingGoalClick = $clickId
            ? DB::table('landings')
            ->where('project_id', $projectId)
            ->where('goal_click_id', $clickId)
            ->where('id', '!=', $id)
            ->exists()
            : false;

        if ($existingGoalClick) {
            return response()->json([
                'errors' => ['goal_click_id' => ['Такая цель перехода уже используется в проекте']],
            ], 422);
        }

        // ❗ Проверка: цель подписки уже есть у другого лендинга
        $existingGoalSubscribe = $subscribeId
            ? DB::table('landings')
            ->where('project_id', $projectId)
            ->where('goal_subscribe_id', $subscribeId)
            ->where('id', '!=', $id)
            ->exists()
            : false;

        if ($existingGoalSubscribe) {
            return response()->json([
                'errors' => ['goal_subscribe_id' => ['Такая цель подписки уже используется в проекте']],
            ], 422);
        }

        // ❗ Проверка: такой URL уже есть у другого лендинга в проекте
        $existingUrl = DB::table('landings')
            ->where('project_id', $projectId)
            ->where('url', $url)
            ->where('id', '!=', $id)
            ->exists();

        if ($existingUrl) {
            return response()->json([
                'errors' => ['url' => ['Такой URL уже используется в проекте']],
            ], 422);
        }

        // ✅ Обновление
        DB::table('landings')->where('id', $id)->update([
            'project_id' => $projectId,
            'name' => $validatedData['name'],
            'url' => $url,
            'description' => $validatedData['description'] ?? null,
            'short_description' => $validatedData['short_description'] ?? null,
            'goal_click_id' => $clickId ?? null,
            'goal_subscribe_id' => $subscribeId ?? null,
            'updated_at' => now(),
        ]);

        $updatedLanding = DB::table('landings')->where('id', $id)->first();

        return response()->json([
            'landing' => $updatedLanding,
            'message' => 'Лендинг успешно обновлён.',
        ], 200);
    }

    /**
     * Удаляет лендинг.
     */
    public function axios_delete_landing($id)
    {
        $landing = DB::table('landings')->where('id', $id)->first();

        if (!$landing) {
            return response()->json(['message' => 'Лендинг не найден.'], 404);
        }

        DB::table('landings')->where('id', $id)->delete();

        return response()->json(['message' => 'Лендинг успешно удалён.'], 200);
    }

    /**
     * Активирует или деактивирует лендинг.
     */
    public function axios_active_landing(Request $request)
    {
        $landing = DB::table('landings')->where('id', $request->id)->first();

        if (!$landing) {
            return response()->json(['message' => 'Лендинг не найден.'], 404);
        }

        DB::table('landings')->where('id', $request->id)->update([
            'is_active' => $request->checked,
        ]);

        $updatedLanding = DB::table('landings')->where('id', $request->id)->first();

        return response()->json([
            'landing' => $updatedLanding,
            'message' => $request->checked ? 'Лендинг активирован.' : 'Лендинг деактивирован.',
        ], 200);
    }
}
