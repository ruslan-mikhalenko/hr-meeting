<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use GuzzleHttp\Client;


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
     * Получить фото канала.
     *
     * @param string $channelUsername
     * @return string|null Путь к сохранённому файлу или null, если фото отсутствует.
     * @throws \Exception
     */
    public function getChannelPhoto($channelUsername)
    {
        try {
            // Инициализируем MadelineProto
            $this->initializeMadeline();

            // Получаем полную информацию о канале
            $response = $this->madeline->getFullInfo($channelUsername);

            // Проверяем наличие фото
            if (isset($response['full']['chat_photo'])) {
                // Получаем объект `chat_photo`
                $chatPhoto = $response['full']['chat_photo'];

                // Указываем публичную директорию для сохранения фото
                $photoDirectory = public_path('channel_photos');
                if (!is_dir($photoDirectory)) {
                    mkdir($photoDirectory, 0755, true);
                }

                // Генерируем имя файла
                $photoPath = $photoDirectory . '/' . $chatPhoto->fileName;

                // Скачиваем фото в указанную директорию
                $this->madeline->downloadToFile($chatPhoto, $photoPath);


                return [
                    'success' => true,
                    'message' => 'Фото успешно загружено.',
                    'file_path' => url('channel_photos/' . $chatPhoto->fileName),
                ];


                // Возвращаем путь внутри public
                /* return response()->json([
                    'success' => true,
                    'message' => 'Фото успешно загружено.',
                    'file_path' => url('channel_photos/' . $chatPhoto->fileName),
                ]); */
            } else {

                return [
                    'success' => false,
                    'message' => 'Фото канала отсутствует.',
                ];


                // Если фото отсутствует
                /* return response()->json([
                    'success' => false,
                    'message' => 'Фото канала отсутствует.',
                ]); */
            }
        } catch (\Exception $e) {
            // Логирование ошибки
            logger()->error("Ошибка при загрузке фото канала: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при загрузке фото канала.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    public function allInfoChannal($channelUsername)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Возвращаем информацию о канале
            return $this->madeline->getFullInfo($channelUsername);
        } catch (\Exception $e) {
            logger()->error("Ошибка получения информации о канале: {$e->getMessage()}");
            throw new \Exception("Ошибка получения информации : {$e->getMessage()}");
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
    /* public function getChannelInfo($channelUsername)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Возвращаем информацию о желаемом канале
            $response = $this->madeline->getFullInfo($channelUsername);
            return $response; // Возвращаем информацию о канале

        } catch (\Exception $e) {
            logger()->error("Ошибка получения информации о канале: {$e->getMessage()}");
            return [];
        }
    } */

    /** ------------------------------------------------------------ */
    public function getChannelInfo($channelUsername)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Возвращаем информацию о желаемом канале
            $response = $this->madeline->getFullInfo($channelUsername);




            // Определяем тип чата (канал, группа, мегагруппа, гигагруппа)
            $chatType = $this->getChatType($response);

            // Определяем приватность чата (публичный или закрытый)
            $chatPrivacy = $this->getChatPrivacy($response);

            // Формируем массив с подробностями о чате
            $chatDetails = [
                'id' => $response['full']['id'] ?? null,
                'title' => $response['Chat']['title'] ?? null,
                'username' => $response['Chat']['username'] ?? null,
                'participants_count' => $response['full']['participants_count'] ?? null,
                'about' => $response['full']['about'] ?? null,
                'type' => $chatType, // Тип чата (канал, группа и т.д.)
                'privacy' => $chatPrivacy, // Приватный или публичный

            ];

            return $chatDetails;
        } catch (\Exception $e) {
            logger()->error("Ошибка получения информации о канале: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Определяет тип Telegram объекта: канал, группа, мегагруппа или гигагруппа
     *
     * @param array $chatInfo Информация о чате, полученная через getFullInfo
     * @return string Тип чата: 'channel', 'megagroup', 'gigagroup', 'group'
     */
    private function getChatType(array $chatInfo): string
    {
        // Если это канал
        if (!empty($chatInfo['Chat']['broadcast']) && $chatInfo['Chat']['broadcast'] === true) {
            return 'channel';
        }

        // Если это мегагруппа
        if (!empty($chatInfo['Chat']['megagroup']) && $chatInfo['Chat']['megagroup'] === true) {
            return 'megagroup';
        }

        // Если это гигагруппа
        if (!empty($chatInfo['Chat']['gigagroup']) && $chatInfo['Chat']['gigagroup'] === true) {
            return 'gigagroup';
        }

        // Если ничего из вышеуказанных случаев, это обычная группа
        return 'group';
    }

    /**
     * Определяет, является ли Telegram-объект публичным или закрытым.
     *
     * @param array $chatInfo Информация о чате, полученная через getFullInfo
     * @return string Тип доступа: 'public' или 'private'
     */
    private function getChatPrivacy(array $chatInfo): string
    {
        // Если username установлен, значит это публичный объект
        if (!empty($chatInfo['Chat']['username'])) {
            return 'public';
        }

        // Если username отсутствует, это закрытый объект
        return 'private';
    }


    /** -------------------------------------------------------------- */

    /* public function getChannelInfo(string $channelUsername)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Используем правильный метод для получения информации о канале
            $response = $this->madeline->channels->getFullChannel([
                'channel' => $channelUsername, // ID, username или InputPeer канала
            ]);

            return $response; // Возвращаем информацию о канале
        } catch (\Exception $e) {
            logger()->error("Ошибка получения информации о канале: {$e->getMessage()}");
            return [];
        }
    } */







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




    public function getAllChannelParticipantsReally($channelId, $limit = 100)
    {
        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            // Получаем подробную информацию о канале/группе
            /*  $chatInfo = $this->madeline->getPwrChat($channelId); */

            $chatInfo = $this->getChannelInfo($channelId);


            // DEBUG: Логируем основную информацию о чате
            Log::info('Chat Info: ' . json_encode($chatInfo));

            // Записываем информацию о чате
            /* $chatDetails = [
                'id' => $chatInfo['full']['id'] ?? null,
                'title' => $chatInfo['Chat']['title'] ?? null,
                'username' => $chatInfo['username'] ?? null,
                'participants_count' => $chatInfo['participants_count'] ?? null,
                'about' => $chatInfo['about'] ?? null,
                'type' => $this->getChatType($chatInfo), // Определяем тип (канал, группа и т.д.)
                'privacy' => $this->getChatPrivacy($chatInfo), // Определяем приватность
            ]; */

            $chatDetails = $chatInfo;

            $allParticipants = [];
            $offset = 0;

            // Обрабатываем участников порциями
            while (true) {
                // Получаем следующую порцию участников
                $batchParticipants = $this->madeline->channels->getParticipants([
                    'channel' => $channelId,
                    'filter' => ['_' => 'channelParticipantsSearch'], // Фильтр: подписчики (можно заменить)
                    'offset' => $offset,
                    'limit' => $limit, // Максимум 200, это ограничение Telegram API
                ]);

                // DEBUG: Логируем информацию о текущей порции
                Log::info("Batch Participants: " . json_encode($batchParticipants));

                if (empty($batchParticipants['users'])) {
                    // Если участников больше нет, выходим из цикла
                    break;
                }

                // Обрабатываем участников текущей порции
                foreach ($batchParticipants['users'] as $user) {
                    $allParticipants[] = [
                        'user_id' => $user['id'] ?? null,
                        'first_name' => $user['first_name'] ?? null,
                        'last_name' => $user['last_name'] ?? null,
                        'username' => $user['username'] ?? null,
                        'phone' => $user['phone'] ?? null,
                        'is_bot' => $user['bot'] ?? false,
                        'status' => $user['status'] ?? null, // Последняя активность
                        'role' => '', // Можно дополнить, если нужно
                    ];
                }

                // Увеличиваем оффсет
                $offset += $limit;

                // Добавляем паузу между запросами (1-2 секунды)
                sleep(2);
            }

            return [
                'chat' => $chatDetails,
                'participants' => $allParticipants,
            ];
        } catch (\Exception $e) {
            Log::error("Ошибка загрузки информации о чате {$channelId}: {$e->getMessage()}");
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




    /**
     * Сохранение подписчиков в базе данных (активные/новые/удалённые подписчики).
     *
     * @param array $currentSubscribers
     */
    private function saveSubscribers(array $currentSubscribers, $channelId)
    {
        // 1. Получение project_id и client_id из таблицы projects по $channelId
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

        // 2. Получение существующих подписчиков из базы для данного project_id
        $existingSubscribers = DB::table('subscribers')
            ->where('project_id', $projectId)
            ->pluck('telegram_user_id')
            ->toArray();

        // 3. Подготовка для добавления новых подписчиков
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
                        'is_active' => 1,
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
                    'is_active' => 1,
                    'subscribed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Log::info("!!!Новые подписчики: " . json_encode($newSubscribers));

        // 4. Вставляем новых подписчиков
        if (!empty($newSubscribers)) {
            DB::table('subscribers')->insert($newSubscribers);
        }

        // 5. Пометка отсутствующих пользователей как неактивных
        $currentUserIds = array_column($currentSubscribers, 'telegram_user_id');

        $missingSubscriberIds = DB::table('subscribers')
            ->where('project_id', $projectId)
            ->whereNotIn('telegram_user_id', $currentUserIds)
            ->pluck('telegram_user_id')
            ->toArray();

        Log::info("Отсутствующие ID подписчиков: " . json_encode($missingSubscriberIds));

        // 6. Асинхронное получение статуса подписчиков
        $client = new Client(); // Guzzle HTTP client
        $promises = [];

        foreach ($missingSubscriberIds as $telegramUserId) {
            // Создаём асинхронный запрос
            $promises[$telegramUserId] = $client->requestAsync('GET', "https://api.telegram.org/bot7591243364:AAGEAx2TqfZkZfMSVa3nhrvizf7v_x1KJMw/getChatMember", [
                'query' => [
                    'chat_id' => $channelId,
                    'user_id' => $telegramUserId,
                ],
            ]);
        }

        $inactiveCount = 0;

        // Обрабатываем результаты запросов
        foreach ($promises as $telegramUserId => $promise) {
            try {
                $response = $promise->wait(); // Ждём выполнения запроса
                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody(), true);

                    if (
                        isset($data['result']['status']) &&
                        in_array($data['result']['status'], ['left', 'kicked']) // Если пользователь покинул канал или был удалён
                    ) {
                        DB::table('subscribers')
                            ->where('project_id', $projectId)
                            ->where('telegram_user_id', $telegramUserId)
                            ->update(['is_active' => 0, 'updated_at' => now()]);
                        $inactiveCount++;
                    }
                }
            } catch (\Exception $e) {
                // Если запрос завершился с ошибкой
                Log::error("Ошибка обработки пользователя $telegramUserId: {$e->getMessage()}");
            }
        }

        Log::info("Обновлено $inactiveCount неактивных подписчиков.");
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
