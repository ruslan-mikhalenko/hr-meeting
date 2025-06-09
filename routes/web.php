<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\TestController;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/* Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard'); */


Route::middleware(['auth'])->group(
    function () {

        /** РОУТЫ ДЛЯ СУПЕР АДМИНА */

        /** Клиенты */
        Route::get('/dashboard', [ClientController::class, 'dashboard'])->name('dashboard');

        Route::post('/filtering_user', [ClientController::class, 'filtering_user'])->name('filtering_user');
        Route::post('/axios_add_user', [ClientController::class, 'axios_add_user'])->name('axios_add_user');
        Route::post('/axios_edit_user', [ClientController::class, 'axios_edit_user'])->name('axios_edit_user');

        Route::put('/axios_update_user/{id}', [ClientController::class, 'axios_update_user'])->name('axios_update_user');
        Route::delete('/axios_delete_user/{id}', [ClientController::class, 'axios_delete_user'])->name('axios_delete_user');
        Route::post('/axios_active_user', [ClientController::class, 'axios_active_user'])->name('axios_active_user');

        /* Поиск кадровика по набираемым данным и вывод в выпадающий select */
        Route::get('/clients', [ClientController::class, 'clients'])->name('clients');

        /** Проекты */

        Route::get('/projects', [ProjectController::class, 'dashboard'])->name('projects.dashboard');
        Route::get('/client/projects/{id}/chart', [ClientController::class, 'getChartData']);

        Route::post('/filtering_project', [ProjectController::class, 'filtering_project'])->name('filtering_project');
        Route::post('/axios_add_project', [ProjectController::class, 'axios_add_project'])->name('axios_add_project');
        Route::post('/axios_edit_project', [ProjectController::class, 'axios_edit_project'])->name('axios_edit_project');

        Route::put('/axios_update_project/{id}', [ProjectController::class, 'axios_update_project'])->name('axios_update_project');
        Route::delete('/axios_delete_project/{id}', [ProjectController::class, 'axios_delete_project'])->name('axios_delete_project');
        Route::post('/axios_active_project', [ProjectController::class, 'axios_active_project'])->name('axios_active_project');

        /* Лэндинги */

        Route::get('/landings', [LandingController::class, 'dashboard'])->name('landings.dashboard');


        Route::post('/filtering_landing', [LandingController::class, 'filtering_landing'])->name('filtering_landing');
        Route::post('/axios_add_landing', [LandingController::class, 'axios_add_landing'])->name('axios_add_landing');
        Route::post('/axios_edit_landing', [LandingController::class, 'axios_edit_landing'])->name('axios_edit_landing');

        Route::put('/axios_update_landing/{id}', [LandingController::class, 'axios_update_landing'])->name('axios_update_landing');
        Route::delete('/axios_delete_landing/{id}', [LandingController::class, 'axios_delete_landing'])->name('axios_delete_landing');
        Route::post('/axios_active_landing', [LandingController::class, 'axios_active_landing'])->name('axios_active_landing');

        /* Поиск проекта по набираемым данным и вывод в выпадающий select */
        Route::get('/projects_search', [ProjectController::class, 'projects_search'])->name('projects.search');


        /** РОУТЫ ДЛЯ КЛИЕНТА */
        Route::get('/project/{id}', [ProjectController::class, 'project'])->name('project');

        Route::post('/filtering_subscribers', [ClientController::class, 'filtering_subscribers'])->name('filtering_subscribers');
        Route::post('/filtering_landings', [ClientController::class, 'filtering_landings'])->name('filtering_landings');

        Route::get('/client/projects/{id}/chart', [ProjectController::class, 'getChartData']);

        Route::get('/client/projects/{project}/cumulative-chart', [ProjectController::class, 'getCumulativeChart']);

        /* Route::get('/dashboard_payment', [PaymentController::class, 'dashboard_payment'])->name('dashboard_payment'); */
    }
);


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/* my routes */
Route::get('/test', [TestController::class, 'test']);

Route::get('/test-token', function () {
    return env('TELEGRAM_BOT_TOKEN', 'Токен не задан');
});


/** Роут для отправки писем с главной */
Route::post('/submit-form', [RequestController::class, 'submitForm'])->name('submit.form');


Route::get('/subscribers', [SubscriberController::class, 'index']);






Route::get('/get-channel-id', function () {
    $telegramService = new TelegramService();

    /* https: //t.me/+8bg_Ns9SXeIzZDNi  - Женщина без возраста*/

    // Укажите приватную ссылку на ваш канал
    /*  $inviteLink = 'https://t.me/+8bg_Ns9SXeIzZDNi'; */
    /*  $inviteLink = 'https://t.me/silaKnig';
 */
    /*  $inviteLink = '-1002288688917'; */
    $inviteLink = 'https://t.me/+D5kQbcwsaCtkYTY6';

    try {
        // Получение информации о канале
        dd($channelInfo = $telegramService->getChannelInfo($inviteLink));

        // Возврат ID канала
        return response()->json([
            'status'      => 'success',
            'channel_id'  => $channelInfo['full']['id'] ?? 'Невозможно определить ID',
            'channel_info' => $channelInfo
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});


Route::get('/info', function (TelegramService $telegramService) {
    $inviteLink = 'https://t.me/+D5kQbcwsaCtkYTY6';

    try {
        $channelInfo = $telegramService->allInfoChannal($inviteLink);
        return response()->json($channelInfo); // Возвращаем JSON-ответ
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});


Route::get('/channel-photo/{username}', function ($username, TelegramService $telegramService) {
    $channelInfo = $telegramService->getChannelPhoto($username);

    /* $channelInfo = $telegramService->getChannelPhoto($validatedData['link']); */
    dd($channelInfo['file_path']);
});


/* Route::get('/test-yandex-metrica', function () {
    $metrikaId = '101725200';
    $goalName = 'new_subscriber';

    // Отправка параметров через вызов JavaScript не работает, поэтому делаем серверный запрос
    $url = "https://mc.yandex.ru/watch/{$metrikaId}";

    $data = [
        "browser-info" => "ti:Custom goal;en:UTF-8",
        "goal" => $goalName,
        "cid" => 12345 // Если требуется идентификатор пользователя
    ];

    try {
        $response = Http::asForm()->post($url, $data);

        if ($response->successful()) {
            Log::info("Цель '{$goalName}' успешно отправлена в Яндекс.Метрику через CRON");
        } else {
            Log::error("Ошибка отправки цели '{$goalName}' через CRON. Статус: {$response->status()}");
        }
    } catch (\Exception $e) {
        Log::error("Ошибка при обработке CRON-задачи для цели '{$goalName}': " . $e->getMessage());
    }

    return 'Задача окончания Cron выполнена!';
}); */


use App\Services\YandexMetrikaService1;
use Illuminate\Support\Str;

Route::get('/test-yandex-metrika', function (YandexMetrikaService1 $metrikaService) {
    // Генерация уникального идентификатора клиента
    $clientId = random_int(100000000, 999999999); // Уникальный Client ID
    $eventAction = 'new_subscriber';   // Название тестового события
    $url = 'https://example.com/test-event'; // Тестовый URL (замените на актуальный)

    // Отправка события через метод sendEvent
    // Вложенные параметры для отправки
    $customParams = [
        'level1_1' => [
            'level2_1' => [
                'level3_1' => 'example1',
                'level3_2' => 'example2',
            ],
        ],
    ];



    $success = $metrikaService->sendEvent($clientId, $eventAction, [
        'url' => $url,
        'custom_params' => $customParams, // Встроенные параметры
    ]);

    // Формирование результата на основе успешности отправки
    return $success
        ? 'Тестовая цель "test_event" успешно отправлена!'
        : 'Ошибка при отправке тестовой цели "test_event". Проверьте лог.';
});


use GuzzleHttp\Client;

Route::get('/test-bot', function () {
    try {
        // Telegram Bot Token
        $botToken = '7591243364:AAGEAx2TqfZkZfMSVa3nhrvizf7v_x1KJMw';

        // Данные для тестирования (замените на реальные значения)
        $channelId = '-1001142192494'; // Например, '@my_channel' или ID -1001234567890
        $telegramUserId = '1559385679'; // ID пользователя Telegram

        // Создаём экземпляр GuzzleHttp Client
        $client = new Client();

        // Запрос к Telegram API
        $response = $client->request('GET', "https://api.telegram.org/bot{$botToken}/getChatMember", [
            'query' => [
                'chat_id' => $channelId, // Укажите канал или ID
                'user_id' => $telegramUserId, // Укажите ID пользователя
            ],
        ]);

        // Получаем и декодируем результат
        $responseBody = json_decode($response->getBody(), true);

        // Возвращаем информацию о пользователе для теста
        return response()->json([
            'status' => 'success',
            'data' => $responseBody,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
});



Route::get('/test-message', function () {
    $chatId = '5639619935'; // или ID чата/канала
    $message = 'Привет от бота!';

    try {
        $telegramService = new TelegramService();
        $telegramService->sendMessage($chatId, $message);
        return 'Сообщение отправлено!';
    } catch (\Exception $e) {
        return 'Ошибка: ' . $e->getMessage();
    }
});

Route::get('/send-bot-message', function () {
    $botToken = env('TELEGRAM_BOT_TOKEN');
    $chatId = '5639619935'; // ID пользователя
    $message = 'Привет от бота!';

    $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
        'chat_id' => $chatId,
        'text' => $message,
    ]);

    if ($response->successful()) {
        return 'Сообщение отправлено ботом!';
    } else {
        return 'Ошибка при отправке сообщения: ' . $response->body();
    }
});



Route::get('/clear', function () {

    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    return "Сброс кэша выполнен!";
});


require __DIR__ . '/auth.php';
