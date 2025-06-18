<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\Services\YandexMetrikaService;

use Illuminate\Support\Str;

class LandingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('show');
        $this->middleware('checkRole:super-admin,client')->except('show');
    }

    /**
     * Отображение дашборда с лендингами.
     */
    public function dashboard(Request $request)
    {
        $user_auth = Auth::user();

        $perPage = 10;
        $currentPage = $request->input('page', 1);



        $landings = DB::table('landings')
            ->select(
                'landings.*',
                'projects.name as project_name',
                'projects.link as project_link',
                'clients.name as client_name'
            )
            ->join('projects', 'landings.project_id', '=', 'projects.id')
            ->leftJoin('clients', 'projects.user_id', '=', 'clients.user_id')
            ->orderByDesc('landings.created_at')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        // Обработка project_link: транслитерация, удаление спецсимволов, нижний регистр
        $landings->getCollection()->transform(function ($landing) {
            $link = $landing->project_link ?? '';

            // Удаление префикса https://t.me/
            $link = str_replace('https://t.me/', '', $link);

            // Преобразуем: транслитерация, удаление лишнего, нижний регистр
            $link = Str::ascii($link); // Преобразование кириллицы в латиницу
            $link = preg_replace('/[^A-Za-z0-9_]/', '', $link); // Удаление спецсимволов и - и +
            $link = Str::lower($link); // Нижний регистр

            $landing->project_link_clean = $link;

            return $landing;
        });


        /*  dd($landings); */


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
            ->select(
                'landings.*',
                'projects.name as project_name',
                'projects.link as project_link',
                'clients.name as client_name' // или другие поля клиента, которые нужны
            )
            ->join('projects', 'landings.project_id', '=', 'projects.id')
            ->leftJoin('clients', 'projects.user_id', '=', 'clients.user_id')
            ->when($search, function ($q) use ($search) {
                $q->where('landings.name', 'LIKE', "%{$search}%")
                    ->orWhere('clients.name', 'LIKE', "%{$search}%");
            })
            ->orderBy($sortField, $sortOrder);


        $landings = $query->paginate($perPage, ['*'], 'page', $currentPage);


        // Обработка project_link: транслитерация, удаление спецсимволов, нижний регистр
        $landings->getCollection()->transform(function ($landing) {
            $link = $landing->project_link ?? '';

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




    public function show($first, $second, $three = null)
    {


        /*  dd($first . ' ' . $second . ' ' .  $three); */

        // $second - это признак того что лендинг с Ботом

        // $three - это признак того что лендинг напрямую на подписку (без бота)




        if ($three) {
            $project = Project::find($three);
            if (!$project) {
                abort(404, 'Project not found');
            }
            $landing_link = 'th-' . $project->id;

            $link_join = $project->link;



            DB::table('events')->insert([
                'project_id' => $project->id,
                'event' => 'transition',
                'when' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $landing = Landing::where('url', $second)->first();
            if (!$landing) {
                abort(404, 'Landing not found');
            }
            $project = Project::find($landing->project_id);
            if (!$project) {
                abort(404, 'Project not found');
            }
            $landing_link = 'sec-' . $project->id;
            $link_join = null;


            DB::table('events')->insert([
                'project_id' => $project->id,
                'landing_id' => $landing->id,
                'event' => 'transition',
                'when' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        /*  dd($project->link); */

        $yandex_metric_id = $project->yandex_metric_id;
        $click_land_goal_id = $project->click_land_goal_id;

        if ($yandex_metric_id && $click_land_goal_id) {

            // Инициализация сервиса Яндекс.Метрики
            $metrikaService = app(YandexMetrikaService::class, ['link' => $project->link]);

            $uniqueUserId = substr(time(), -5) . rand(10000, 99999);

            $metrikaService->sendEvent($uniqueUserId, $click_land_goal_id, [

                'url' => $project->link,
            ]);
        }

        /* dd($click_land_goal_id); */


        return Inertia::render('LandingDashboard', [

            'landing_link' => $landing_link,
            'yandex_metric_id' => $yandex_metric_id,
            'link_join' => $link_join,
        ]);
    }
}
