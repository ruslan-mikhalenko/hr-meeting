<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Получение всех подписчиков канала.
     *
     * @param string $channelId
     * @return array
     */
    public function getAllChannelParticipants($channelId)
    {
        $allParticipants = []; // Массив для всех участников

        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Используем метод getPwrChat для загрузки полной информации о чате
            $chatInfo = $this->madeline->getPwrChat($channelId);

            // Проверяем наличие участников в данных чата
            if (isset($chatInfo['participants']) && is_array($chatInfo['participants'])) {
                foreach ($chatInfo['participants'] as $participant) {
                    if (!isset($participant['user'])) {
                        continue;
                    }
                    $user = $participant['user'];

                    // Добавляем участника в массив $allParticipants
                    $allParticipants[] = [
                        'user_id' => $user['id'] ?? null,
                        'first_name' => $user['first_name'] ?? null,
                        'last_name' => $user['last_name'] ?? null,
                        'username' => $user['username'] ?? null,
                        'phone' => $user['phone'] ?? null,
                        'status' => $user['status'] ?? null, // Статус активности
                        'role' => $participant['_'] ?? null, // Роль участника в чате
                    ];
                }
            }
        } catch (\Exception $e) {
            // Логируем ошибки
            Log::error("Ошибка при получении участников канала {$channelId}: {$e->getMessage()}");
        }

        // Возвращаем только список участников
        return $allParticipants;
    }


    public function getAllChannelParticipantsReally($channelId)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Получаем подробную информацию о канале/группе
            $chatInfo = $this->madeline->getPwrChat($channelId);

            // DEBUG: Логируем основную информацию о чате
            Log::info('Chat Info: ' . json_encode($chatInfo));

            // Записываем информацию о чате
            $chatDetails = [
                'id' => $chatInfo['id'] ?? null,
                'title' => $chatInfo['title'] ?? null,
                'username' => $chatInfo['username'] ?? null,
                'participants_count' => $chatInfo['participants_count'] ?? null,
                'about' => $chatInfo['about'] ?? null,
                'type' => $this->getChatType($chatInfo), // Определяем тип (канал, группа и т.д.)
                'privacy' => $this->getChatPrivacy($chatInfo), // Определяем приватность
            ];

            // Проверяем наличие участников
            if (isset($chatInfo['participants']) && count($chatInfo['participants']) > 0) {
                $allParticipants = [];

                foreach ($chatInfo['participants'] as $participant) {
                    if (isset($participant['user'])) {
                        $user = $participant['user'];

                        // Получаем дополнительные данные о пользователе
                        $userDetails = $this->getUserDetails($user['id']);

                        $allParticipants[] = [
                            'user_id' => $user['id'] ?? null,
                            'first_name' => $user['first_name'] ?? null,
                            'last_name' => $user['last_name'] ?? null,
                            'username' => $user['username'] ?? null,
                            'phone' => $user['phone'] ?? null,
                            'role' => $participant['_'] ?? null, // Роль участника
                            'is_bot' => $user['bot'] ?? false,
                            'status' => $user['status'] ?? null, // Последняя активность
                            'user_additional_info' => $userDetails,
                        ];
                    }
                }

                return [
                    'chat' => $chatDetails,
                    'participants' => $allParticipants,
                ];
            }

            // Если участников нет, возвращаем только информацию о чате
            return [
                'chat' => $chatDetails,
                'participants' => [],
            ];
        } catch (\Exception $e) {
            Log::error("Ошибка загрузки информации о чате {$channelId}: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Получение дополнительных данных о пользователе
     */
    private function getUserDetails($userId)
    {
        try {
            // Используем метод users.getFullUser для получения информации о пользователе
            $response = $this->madeline->users->getFullUser([
                'id' => $userId,
            ]);
            $userInfo = $response['user'] ?? null;

            return [
                'about' => $response['about'] ?? null, // Описание пользователя
                'common_chats_count' => $response['common_chats_count'] ?? null, // Количество общих чатов
                'profile_photo' => $userInfo['photo'] ?? null, // Фотография профиля
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
            $userId = $subscriber['user_id'];

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
                        'is_active' => true,
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
                    'is_active' => true,
                    'subscribed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Вставляем новых подписчиков
        if (!empty($newSubscribers)) {
            DB::table('subscribers')->insert($newSubscribers);
        }

        // Помечаем пользователей, отсутствующих в новом списке, как неактивных
        $currentUserIds = array_column($currentSubscribers, 'user_id'); // Извлекаем user_id из $currentSubscribers
        $removedSubscribers = DB::table('subscribers')
            ->where('project_id', $projectId)
            ->whereNotIn('telegram_user_id', $currentUserIds)
            ->pluck('telegram_user_id')
            ->toArray();

        if (!empty($removedSubscribers)) {
            DB::table('subscribers')
                ->where('project_id', $projectId)
                ->whereIn('telegram_user_id', $removedSubscribers)
                ->update(['is_active' => false, 'updated_at' => now()]);
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
            // Инициализация сервиса Яндекс.Метрики
            $metrikaService = app(YandexMetrikaService::class);

            // Получение текущих подписчиков канала (полные данные)
            $allParticipants = $this->getAllChannelParticipants($channelId);

            if (empty($allParticipants)) {
                Log::info("Для канала {$channelId} не найдено подписчиков.");
                return;
            }

            // Преобразуем данные для удобства обработки
            $currentSubscribers = array_column($allParticipants, null, 'user_id'); // Индексируем участников по user_id

            // Получаем подписчиков из базы данных
            $storedSubscribers = DB::table('subscribers')
                ->select('user_id', 'is_active')
                ->get()
                ->keyBy('user_id')
                ->toArray();

            // Вычисляем новых подписчиков и возвращённых подписчиков
            $newSubscribers = [];
            $returningSubscribers = [];

            foreach ($currentSubscribers as $userId => $subscriberInfo) {
                if (!isset($storedSubscribers[$userId])) {
                    // Если подписчика нет в базе данных, это новый подписчик
                    $newSubscribers[] = $subscriberInfo; // Полная информация о подписчике
                } elseif (!$storedSubscribers[$userId]->is_active) {
                    // Если подписчик был в базе, но неактивен, он вернулся
                    $returningSubscribers[] = $subscriberInfo; // Полная информация о подписчике
                }
            }

            // Отправка событий для новых подписчиков
            foreach ($newSubscribers as $subscriberInfo) {
                $metrikaService->sendEvent($subscriberInfo['user_id'], 'new_subscriber', [
                    'name' => $subscriberInfo['first_name'] ?? null,
                    'username' => $subscriberInfo['username'] ?? null,
                    'url' => 'https://t.me/komplemir_by',
                ]);
            }

            // Отправка событий для возвращённых подписчиков
            foreach ($returningSubscribers as $subscriberInfo) {
                $metrikaService->sendEvent($subscriberInfo['user_id'], 'returning_subscriber', [
                    'name' => $subscriberInfo['first_name'] ?? null,
                    'username' => $subscriberInfo['username'] ?? null,
                    'url' => 'https://t.me/komplemir_by',
                ]);
            }

            // Сохранение текущего состояния подписчиков в базе
            $this->saveSubscribers(
                $allParticipants,
                $channelId
            ); // Передаём полные данные о подписчиках проекта

            Log::info("Успешно обработаны новые и возвращённые подписчики для канала {$channelId}.");
        } catch (\Exception $e) {
            Log::error("Ошибка при обработке подписчиков для канала {$channelId}: {$e->getMessage()}");
        }
    }
}
