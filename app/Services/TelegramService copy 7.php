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
        $allParticipants = [];
        $offset = 0;
        $limit = 200;

        try {
            // Инициализируем клиент MadelineProto
            $this->initializeMadeline();

            do {
                // Получаем участников батчами, начиная с установленного смещения
                $response = $this->madeline->channels->getParticipants([
                    'channel' => $channelId,
                    'filter'  => ['_' => 'channelParticipantsRecent'],
                    'offset'  => $offset,
                    'limit'   => $limit,
                ]);

                if (isset($response['participants']) && count($response['participants']) > 0) {
                    $allParticipants = array_merge($allParticipants, $response['participants']);
                    $offset += $limit;
                } else {
                    break;
                }
            } while (true);
        } catch (\Exception $e) {
            Log::error("Ошибка загрузки участников канала {$channelId}: {$e->getMessage()}");
            return [];
        }

        return $allParticipants;
    }



    public function getAllChannelParticipantsReally($channelId)
    {



        try {
            // Инициализация MadelineProto
            $this->initializeMadeline();

            // Получаем данные о канале
            $response = $this->madeline->getPwrChat($channelId);

            // Формируем ответ с подробной информацией
            $channelInfo = [
                'id' => $response['id'] ?? null,
                'type' => $response['type'] ?? 'Unknown', // Тип канала (группа/супергруппа/канал)
                'title' => $response['title'] ?? 'Unknown Channel', // Название канала
                'username' => $response['username'] ?? null, // @username канала
                'about' => $response['about'] ?? null, // Описание канала
                'photo' => isset($response['photo']) ? $this->extractPhotoData($response['photo']) : null, // Обработка фотографии
                'invite' => $response['invite'] ?? null, // Ссылка-приглашение

                // Статистика
                'statistics' => [
                    'participants_count' => $response['participants_count'] ?? 0, // Количество участников
                    'admins_count' => $response['admins_count'] ?? 0, // Количество администраторов
                    'banned_count' => $response['banned_count'] ?? 0, // Количество заблокированных пользователей
                    'kicked_count' => $response['kicked_count'] ?? 0, // Количество исключённых пользователей
                ],

                // Права доступа
                'permissions' => [
                    'can_set_username' => $response['can_set_username'] ?? false,
                    'can_set_stickers' => $response['can_set_stickers'] ?? false,
                    'can_view_participants' => $response['can_view_participants'] ?? false,
                    'can_view_stats' => $response['can_view_stats'] ?? false,
                    'can_pin_messages' => $response['can_pin_messages'] ?? false,
                ],

                // Участники
                'participants' => [],
            ];

            // Обрабатываем пользователей и участников
            if (isset($response['participants'])) {
                foreach ($response['participants'] as $participant) {
                    // Проверка информации о пользователе
                    $user = $participant['user'] ?? null;

                    if ($user) {
                        $channelInfo['participants'][] = [
                            'user_id' => $user['id'] ?? null,
                            'first_name' => $user['first_name'] ?? null,
                            'last_name' => $user['last_name'] ?? null,
                            'username' => $user['username'] ?? null,
                            'phone' => $user['phone'] ?? null,
                            'role' => $participant['_'] ?? null, // Роль участника (например, admin или creator)
                        ];
                    }
                }
            }

            return $channelInfo;
        } catch (\Exception $e) {
            Log::error("Ошибка получения подробной информации о канале {$channelId}: {$e->getMessage()}");
            return [];
        }
    }


    /**
     * Извлечение данных из объекта photo
     *
     * @param mixed $photoObject Объект photo, возвращаемый MadelineProto
     * @return array|null
     */
    private function extractPhotoData($photoObject)
    {
        try {
            // Проверяем, является ли объект корректным
            if ($photoObject instanceof \danog\MadelineProto\EventHandler\Media\Photo) {
                $sizes = [];
                if (property_exists($photoObject, 'sizes')) {
                    foreach ($photoObject->sizes as $size) {
                        $sizes[] = [
                            'type' => $size->type ?? null, // Тип размера (например, "s", "m")
                            'width' => $size->w ?? null,  // Ширина
                            'height' => $size->h ?? null, // Высота
                        ];
                    }
                }

                return [
                    'id' => $photoObject->id ?? null, // ID фотографии (если доступен)
                    'sizes' => $sizes, // Доступные размеры
                ];
            }
        } catch (\Exception $e) {
            Log::error("Ошибка обработки объекта photo: {$e->getMessage()}");
        }

        return null; // Если объект некорректен или произошла ошибка
    }





    /**
     * Сохранение подписчиков в базе данных (активные/новые/удалённые подписчики).
     *
     * @param array $currentSubscribers
     */
    private function saveSubscribers(array $currentSubscribers)
    {
        $existingSubscribers = DB::table('subscribers')
            ->select('user_id', 'is_active')
            ->get()
            ->keyBy('user_id')
            ->toArray();

        $newSubscribers = array_diff($currentSubscribers, array_keys($existingSubscribers));

        foreach ($currentSubscribers as $userId) {
            if (isset($existingSubscribers[$userId]) && !$existingSubscribers[$userId]->is_active) {
                DB::table('subscribers')
                    ->where('user_id', $userId)
                    ->update(['is_active' => true, 'updated_at' => now()]);
            }
        }

        $removedSubscribers = array_diff(array_keys($existingSubscribers), $currentSubscribers);

        if (!empty($removedSubscribers)) {
            DB::table('subscribers')
                ->whereIn('user_id', $removedSubscribers)
                ->update(['is_active' => false, 'updated_at' => now()]);
        }

        foreach ($newSubscribers as $userId) {
            DB::table('subscribers')->updateOrInsert(
                ['user_id' => $userId],
                ['is_active' => true, 'subscribed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
            );
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

            // Получение текущих подписчиков канала
            $allParticipants = $this->getAllChannelParticipants($channelId);
            $currentSubscribers = array_column($allParticipants, 'user_id'); // Извлекаем только user_id

            if (empty($currentSubscribers)) {
                Log::info("Для канала {$channelId} не найдено подписчиков.");
                return;
            }

            // Получаем подписчиков из базы
            $storedSubscribers = DB::table('subscribers')
                ->select('user_id', 'is_active')
                ->get()
                ->keyBy('user_id')
                ->toArray();

            // Вычисляем новых подписчиков
            $newSubscribers = array_diff($currentSubscribers, array_keys($storedSubscribers));

            // Вычисляем возвращённых подписчиков (были в базе, но имели is_active = 0)
            $returningSubscribers = [];
            foreach ($currentSubscribers as $subscriberId) {
                if (isset($storedSubscribers[$subscriberId]) && !$storedSubscribers[$subscriberId]->is_active) {
                    $returningSubscribers[] = $subscriberId;
                }
            }

            // Отправка события для новых подписчиков
            foreach ($newSubscribers as $subscriberId) {
                $metrikaService->sendEvent($subscriberId, 'new_subscriber', [
                    'url' => 'https://t.me/komplemir_by',
                ]);
            }

            // Отправка события для возвращённых подписчиков
            foreach ($returningSubscribers as $subscriberId) {
                $metrikaService->sendEvent($subscriberId, 'new_subscriber', [
                    'url' => 'https://t.me/komplemir_by',
                ]);
            }

            // Сохранение текущего состояния подписчиков в базе
            $this->saveSubscribers($currentSubscribers);

            Log::info("Успешно обработаны новые и возвращённые подписчики для канала {$channelId}.");
        } catch (\Exception $e) {
            Log::error("Ошибка при обработке подписчиков для канала {$channelId}: {$e->getMessage()}");
        }
    }
}
