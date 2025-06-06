<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


use Amp\Sync\LocalSemaphore;
use Amp\Sync\Lock;
use function Amp\delay;
use function Amp\async;


class TelegramService
{
    private $madeline;
    private $sessionPath;

    /**
     * TelegramService Constructor
     *
     * Вынесенная базовая инициализация сессии, но запуск MadelineProto оставлен ленивым.
     */
    public function __construct()
    {
        // Указание пути для хранения сессии MadelineProto
        $this->sessionPath = storage_path('app/telegram.session');
    }

    /**
     * Ленивая инициализация MadelineProto (только если это нужно).
     *
     * @throws \Exception
     */
    private function initializeMadeline()
    {
        if (!$this->madeline) {
            $settings = new Settings([
                'app_info' => [
                    'api_id' => (int) env('TELEGRAM_API_ID'),     // Берём ID из переменных среды
                    'api_hash' => env('TELEGRAM_API_HASH'),       // Берём Hash из переменных среды
                ],
                'ipc' => [
                    'enable' => false, // Отключаем IPC для избежания временной синхронизации
                ],
            ]);

            // Создание экземпляра API и запуск сессии
            $this->madeline = new API($this->sessionPath, $settings);
            $this->madeline->start();
        }
    }

    /**
     * Получить сам объект текущего авторизованного аккаунта.
     *
     * @return array
     * @throws \Exception
     */
    public function getSelf()
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Возвращаем данные текущего авторизованного пользователя
            return $this->madeline->getSelf();
        } catch (\Exception $e) {
            throw new \Exception("Ошибка получения информации о боте: {$e->getMessage()}");
        }
    }

    /**
     * Отправить сообщение в конкретный чат (peer).
     *
     * @param int|string $chatId
     * @param string $text
     * @throws \Exception
     */
    public function sendMessage($chatId, $text)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Используем MadelineProto для отправки сообщения через API
            $this->madeline->messages->sendMessage([
                'peer'    => $chatId, // Идентификатор чата/пользователя
                'message' => $text,   // Текст сообщения
            ]);
        } catch (Exception $e) {
            throw new \Exception("Ошибка отправки сообщения: {$e->getMessage()}");
        }
    }

    /**
     * Получить список участников указанного канала.
     *
     * @param string $channelUsername
     * @return array
     * @throws \Exception
     */
    public function getChannelParticipants($channelUsername)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Запрашиваем полную информацию о канале
            $fullInfo = $this->madeline->getFullInfo($channelUsername);

            // Возвращаем список участников
            return $fullInfo['participants'] ?? [];
        } catch (\Exception $e) {
            logger()->error("Ошибка получения участников канала: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Получить основную информацию о канале.
     *
     * @param string $channelUsername
     * @return array
     * @throws \Exception
     */
    public function getChannelInfo($channelUsername)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Возвращаем информацию о желаемом канале
            return $this->madeline->getFullInfo($channelUsername);
        } catch (\Exception $e) {
            logger()->error("Ошибка получения информации о канале: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Порционное получение подписчиков канала/группы с паузами.
     *
     * @param string $channelId
     * @param int $limit Количество подписчиков за один запрос (рекомендуется 200-500)
     * @return array
     * @throws \Exception
     */
    public function getChannelParticipantsBatch(string $channelId, int $limit = 200)
    {
        $allParticipants = [];
        $offset = 0;

        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            while (true) {
                // Получаем порцию участников
                $participants = $this->madeline->channels->getParticipants([
                    'channel' => $channelId,
                    'filter' => ['_' => 'channelParticipantsRecent'],
                    'offset' => $offset,
                    'limit' => $limit,
                ]);

                // Если участников нет, завершаем
                if (empty($participants['users'])) {
                    break;
                }

                // Обрабатываем участников
                foreach ($participants['users'] as $user) {
                    $allParticipants[] = [
                        'telegram_user_id' => $user['id'] ?? null,
                        'first_name' => $user['first_name'] ?? null,
                        'last_name' => $user['last_name'] ?? null,
                        'username' => $user['username'] ?? null,
                        'phone' => $user['phone'] ?? null,
                        'status' => $user['status'] ?? null,
                    ];
                }

                // Увеличиваем оффсет для следующей порции
                $offset += $limit;

                // Пауза между запросами (например, 2 секунды)
                sleep(2);
            }
        } catch (\Exception $e) {
            Log::error("Ошибка при получении участников канала {$channelId}: {$e->getMessage()}");
        }

        return $allParticipants;
    }




    public function getAllChannelParticipantsReally($channelId, $limit = 100, $parallelLimit = 5)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Получаем информацию о канале
            $chatInfo = $this->madeline->getPwrChat($channelId);

            $participantsCount = $chatInfo['participants_count'] ?? 0;
            if ($participantsCount === 0) {
                return [
                    'chat' => $chatInfo,
                    'participants' => [],
                ];
            }

            // Ограничитель степени параллелизма
            $semaphore = new LocalSemaphore($parallelLimit);

            $allParticipants = [];
            $promises = [];
            for ($offset = 0; $offset < $participantsCount; $offset += $limit) {
                $promises[] = async(function () use ($semaphore, $channelId, $offset, $limit) {
                    // Получаем доступ к семафору
                    $lock = $semaphore->acquire();
                    try {
                        // Выполняем запрос
                        delay(1); // Задержка в 1 секунду
                        return $this->madeline->channels->getParticipants([
                            'channel' => $channelId,
                            'filter' => ['_' => 'channelParticipantsRecent'],
                            'offset' => $offset,
                            'limit' => $limit,
                        ]);
                    } finally {
                        // Освобождаем семафор после завершения
                        $lock->release();
                    }
                });
            }

            // Ждем завершения всех запросов
            $results = [];
            foreach ($promises as $promise) {
                $results[] = $promise->await();
            }

            // Обрабатываем результаты
            foreach ($results as $batchParticipants) {
                if (!empty($batchParticipants['users'])) {
                    foreach ($batchParticipants['users'] as $user) {
                        $allParticipants[] = [
                            'user_id' => $user['id'] ?? null,
                            'first_name' => $user['first_name'] ?? null,
                            'last_name' => $user['last_name'] ?? null,
                            'username' => $user['username'] ?? null,
                            'phone' => $user['phone'] ?? null,
                            'is_bot' => $user['bot'] ?? false,
                            'status' => $user['status'] ?? null,
                            'role' => '',
                        ];
                    }
                }
            }

            return [
                'chat' => $chatInfo,
                'participants' => $allParticipants,
            ];
        } catch (\Exception $e) {
            Log::error("Ошибка: {$e->getMessage()}");
            return [
                'chat' => [],
                'participants' => [],
            ];
        }
    }


    /**
     * Получение дополнительных данных о пользователе
     */
    private function getUserDetails($userId)
    {
        try {
            if (!is_numeric($userId)) {
                throw new \Exception("ID пользователя должен быть числом: {$userId}");
            }

            // Используем метод users.getFullUser для получения информации о пользователе
            $response = $this->madeline->users->getFullUser([
                'id' => (int) $userId,
            ]);
            $userInfo = $response['user'] ?? null;

            return [
                'about' => $response['about'] ?? null,
                'common_chats_count' => $response['common_chats_count'] ?? null,
                'profile_photo' => $userInfo['photo'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::warning("Ошибка при получении информации о пользователе {$userId}: {$e->getMessage()}");
            return null;
        }
    }




    private function getChatType($chatInfo)
    {
        // Основной тип, как его определяет MadelineProto
        $type = $chatInfo['_'] ?? null;

        // Проверяем, является ли тип "channel"
        if ($type === 'channel') {
            // Проверяем, является ли объект супергруппой
            if (isset($chatInfo['megagroup']) && $chatInfo['megagroup'] === true) {
                return 'supergroup'; // Это супергруппа
            }
            return 'channel'; // Это канал
        }

        // Проверяем, является ли это обычной группой (chat)
        if ($type === 'chat') {
            // Проверим, есть ли характерные признаки канала
            if (isset($chatInfo['username']) && !empty($chatInfo['username'])) {
                return 'channel'; // Канал, так как есть username
            }
            if (isset($chatInfo['participants_count']) && $chatInfo['participants_count'] > 0) {
                return 'group'; // Группа, так как есть участники
            }
        }

        // Если тип не определён явно, проверим другие поля
        if (!isset($chatInfo['username']) && isset($chatInfo['participants_count']) && $chatInfo['participants_count'] > 0) {
            if (isset($chatInfo['about']) && str_starts_with($chatInfo['about'], 'https://t.me/')) {
                return 'channel'; // Предположительно канал, если участники есть и есть приглашение
            }
            return 'group'; // Обычная группа
        }

        if (isset($chatInfo['username']) && !empty($chatInfo['username'])) {
            return 'channel'; // Канал из-за наличия username
        }

        // Вернем тип по умолчанию, если ничего не подошло
        return 'unknown';
    }



    private function getChatPrivacy($chatInfo)
    {
        // Если есть username, то группа/канал открытые
        if (isset($chatInfo['username']) && !empty($chatInfo['username'])) {
            return 'public';
        }

        // Если username нет, то это закрытая группа/канал
        return 'private';
    }



    /**
     * Сохранение подписчиков в базе данных (активные/новые/удалённые подписчики).
     *
     * @param array $currentSubscribers
     */
    private function saveSubscribers(array $currentSubscribers, $channelId)
    {
        // Вытаскиваем project_id и client_id из таблицы projects по channelId
        $project = DB::table('projects')
            ->select('id as project_id', 'user_id as client_id')
            ->where('link', $channelId)
            ->first();

        if (!$project) {
            Log::warning("Проект не найден для канала: {$channelId}");
            return;
        }

        $projectId = $project->project_id;
        $clientId = $project->client_id;

        // Получаем существующих подписчиков из базы для данного project_id
        $existingSubscribers = DB::table('subscribers')
            ->where('project_id', $projectId)
            ->pluck('telegram_user_id')
            ->toArray();

        // Массив для новых подписчиков
        $newSubscribers = [];

        foreach ($currentSubscribers as $subscriber) {
            $userId = $subscriber['telegram_user_id'];

            if (in_array($userId, $existingSubscribers)) {
                // Если подписчик уже существует, обновляем его информацию
                DB::table('subscribers')
                    ->where('project_id', $projectId)
                    ->where('telegram_user_id', $userId)
                    ->update([
                        'first_name' => $subscriber['first_name'] ?? null,
                        'last_name' => $subscriber['last_name'] ?? null,
                        'username' => $subscriber['username'] ?? null,
                        'phone' => $subscriber['phone'] ?? null,
                        'is_active' => 1, // Явное приведение к числу
                        'updated_at' => now(),
                    ]);
            } else {
                // Если подписчик новый, добавляем его в массив
                $newSubscribers[] = [
                    'project_id' => $projectId,
                    'user_id' => $clientId,
                    'telegram_user_id' => $userId,
                    'first_name' => $subscriber['first_name'] ?? null,
                    'last_name' => $subscriber['last_name'] ?? null,
                    'username' => $subscriber['username'] ?? null,
                    'phone' => $subscriber['phone'] ?? null,
                    'is_active' => 1, // Явное приведение к числу
                    'subscribed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Log::info("!!!Новые подписчики: " . json_encode($newSubscribers));


        // Вставляем новых подписчиков
        if (!empty($newSubscribers)) {
            DB::table('subscribers')->insert($newSubscribers);
        }



        // Помечаем отсутствующих пользователей как неактивных
        $currentUserIds = array_column($currentSubscribers, 'telegram_user_id');

        if (!empty($currentUserIds)) {
            DB::table('subscribers')
                ->where('project_id', $projectId)
                ->whereNotIn('telegram_user_id', $currentUserIds)
                ->update(['is_active' => 0, 'updated_at' => now()]);
        }
    }



    /**
     * Трекинг новых подписчиков в канале и обработка данных Яндекс.Метрики.
     *
     * @param string $channelId
     */
    public function trackNewSubscribers(string $channelId)
    {
        try {
            // Получение данных проекта по $channelId
            $project = DB::table('projects')->where('link', $channelId)->first();

            if (!$project || empty($project->goal_id)) {
                Log::error("Проект для channelId {$channelId} не найден или не указан goal_id.");
                return;
            }

            $goalId = $project->goal_id;

            // Инициализация сервиса Яндекс.Метрики
            $metrikaService = app(YandexMetrikaService::class, ['link' => $channelId]);

            // Получение текущих подписчиков канала (порциями)
            $allParticipants = $this->getChannelParticipantsBatch($channelId);

            if (empty($allParticipants)) {
                Log::info("Для канала {$channelId} не найдено подписчиков.");
                return;
            }

            // Преобразуем данные для обработки
            $currentSubscribers = array_column($allParticipants, null, 'telegram_user_id');

            // Получаем подписчиков из базы данных
            $storedSubscribers = DB::table('subscribers')
                ->select('telegram_user_id', 'is_active')
                ->where('project_id', $project->id)
                ->get()
                ->keyBy('telegram_user_id')
                ->toArray();

            // Вычисляем новых подписчиков и возвращённых подписчиков
            $newSubscribers = [];
            $returningSubscribers = [];

            foreach ($currentSubscribers as $telegramUserId => $subscriberInfo) {
                if (!isset($storedSubscribers[$telegramUserId])) {
                    $newSubscribers[] = $subscriberInfo;
                } elseif (!$storedSubscribers[$telegramUserId]->is_active) {
                    $returningSubscribers[] = $subscriberInfo;
                }
            }

            // Отправка событий в Яндекс.Метрику для новых и возвращённых подписчиков
            foreach (array_merge($newSubscribers, $returningSubscribers) as $subscriberInfo) {
                $metrikaService->sendEvent($subscriberInfo['telegram_user_id'], $goalId, [
                    'name' => $subscriberInfo['first_name'] ?? null,
                    'username' => $subscriberInfo['username'] ?? null,
                    'url' => 'https://t.me/' . $channelId,
                ]);
            }

            // Сохранение текущего состояния подписчиков в базе
            $this->saveSubscribers($allParticipants, $channelId);
        } catch (\Exception $e) {
            Log::error("Ошибка при обработке подписчиков для канала {$channelId}: {$e->getMessage()}");
        }
    }
}
