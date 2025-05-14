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

    public function __construct()
    {
        // Указание пути для хранения сессии
        $this->sessionPath = storage_path('app/telegram.session');

        // Инициализация MadelineProto
        $this->initializeMadeline();
    }

    /**
     * Метод для инициализации MadelineProto
     */
    private function initializeMadeline()
    {
        if (!$this->madeline) {
            // Настройка параметров API
            $settings = new Settings([
                'app_info' => [
                    'api_id'  => (int) env('TELEGRAM_API_ID'), // API ID
                    'api_hash' => env('TELEGRAM_API_HASH'),    // API Hash
                ],
            ]);

            // Инициализация и запуск MadelineProto
            $this->madeline = new API($this->sessionPath, $settings);
            $this->madeline->start(); // Выполняем старт сессии
        }
    }


    /**
     * Получить информацию о текущем боте
     *
     * @return array
     * @throws \Exception
     */
    public function getSelf()
    {
        try {
            return $this->madeline->getSelf();
        } catch (\Exception $e) {
            throw new \Exception("Ошибка получения информации о боте: {$e->getMessage()}");
        }
    }



    /**
     * Sends a message to a specific chat.
     *
     * @param int|string $chatId
     * @param string $text
     * @return void
     * @throws \Exception
     */
    public function sendMessage($chatId, $text)
    {
        try {
            // Use MadelineProto to send the message
            $this->madeline->messages->sendMessage([
                'peer'    => $chatId, // ID of the chat or username
                'message' => $text,   // Text of the message
            ]);
        } catch (Exception $e) {
            throw new \Exception("Ошибка отправки сообщения: {$e->getMessage()}");
        }
    }

    /**
     * Получение списка участников канала
     *
     * @param string $channelUsername
     * @return array
     */
    public function getChannelParticipants($channelUsername)
    {
        try {
            // Получаем полную информацию о канале
            $fullInfo = $this->madeline->getFullInfo($channelUsername);

            // Возвращаем список участников
            return $fullInfo['participants'] ?? [];
        } catch (\Exception $e) {
            // Логируем ошибку
            logger()->error("Ошибка получения участников канала: {$e->getMessage()}");

            // Возвращаем пустой массив в случае ошибки
            return [];
        }
    }

    /**
     * Получить информацию о канале через MadelineProto
     *
     * @param string $channelUsername
     * @return array
     */
    public function getChannelInfo($channelUsername)
    {
        try {
            // Получение полной информации о канале
            return $this->madeline->getFullInfo($channelUsername);
        } catch (\Exception $e) {
            // Логирование ошибок
            logger()->error("Ошибка получения информации о канале: {$e->getMessage()}");
            return [];
        }
    }

    /* ---------------------------------------------- */

    /**
     * Отслеживание новых подписчиков в канале.
     */
    public function trackNewSubscribers(string $channelId)
    {
        try {
            // Получение всех текущих участников канала
            $allParticipants = $this->getAllChannelParticipants($channelId);
            $currentSubscribers = array_column($allParticipants, 'user_id');

            if (empty($currentSubscribers)) {
                Log::info("Для канала {$channelId} не найдено текущих подписчиков.");
                return;
            }

            // Получение существующих подписчиков из базы
            $storedSubscribers = DB::table('subscribers')->pluck('user_id')->toArray();

            // Новые подписчики
            $newSubscribers = array_diff($currentSubscribers, $storedSubscribers);

            // Обработка новых подписчиков
            foreach ($newSubscribers as $subscriberId) {
                $this->sendToYandexMetrica($subscriberId);
            }

            // Сохранение подписчиков в базе
            $this->saveSubscribers($currentSubscribers);

            Log::info("Успешно обработаны новые подписчики для канала {$channelId}.");
        } catch (\Exception $e) {
            Log::error("Ошибка при обработке новых подписчиков для канала {$channelId}: {$e->getMessage()}");
        }
    }

    /**
     * Пример метода отправки данных в Яндекс.Метрику.
     */
    public function sendToYandexMetrica($userId)
    {
        $metrikaId = env('YANDEX_METRIKA_ID'); // ID вашего счётчика в Яндекс.Метрике
        $oauthToken = env('YANDEX_METRIKA_OAUTH'); // OAuth-токен для доступа к API Яндекс.Метрики


        if (!$metrikaId || !$oauthToken) {
            Log::warning("YANDEX_METRIKA_ID или YANDEX_METRIKA_OAUTH не настроены. Данные для пользователя {$userId} не были отправлены.");
            return;
        }

        // URL для отправки события в Яндекс.Метрику
        $url = "https://api-metrika.yandex.net/management/v1/counter/{$metrikaId}/events";

        // Данные события, которые мы отправим в API
        $data = [
            'type' => 'goal',                 // Указываем тип события как "цель"
            'name' => 'new_subscriber',       // Имя события или цели
            'user_id' => $userId,             // ID пользователя (или session ID)
        ];

        try {
            // Выполняем HTTP-запрос через Laravel Http Client
            $response = Http::withToken($oauthToken) // Указываем OAuth-токен в заголовке
                ->timeout(10)                        // Устанавливаем таймаут для запроса
                ->post($url, $data);                 // Выполняем POST-запрос с телом, содержащим данные цели события

            if ($response->successful()) {
                Log::info("Событие 'new_subscriber' успешно отправлено в Яндекс.Метрику для пользователя {$userId}.");
            } else {
                Log::error("Ошибка отправки события в Яндекс.Метрику для пользователя {$userId}. Статус: {$response->status()}. Ответ: {$response->body()}");
            }
        } catch (\Exception $e) {
            // Ловим исключения и записываем их в лог
            Log::error("Ошибка отправки события в Яндекс.Метрику для пользователя {$userId}: {$e->getMessage()}");
        }
    }



    /**
     * Сохранение подписчиков.
     */
    private function saveSubscribers(array $currentSubscribers)
    {
        // Получаем список существующих подписчиков из базы данных
        $existingSubscribers = DB::table('subscribers')
            ->select('user_id', 'is_active')
            ->get()
            ->keyBy('user_id')
            ->toArray(); // Преобразуем в массив для удобства работы

        // Находим новых подписчиков
        $newSubscribers = array_diff($currentSubscribers, array_keys($existingSubscribers));

        // Логика восстановления подписчиков
        foreach ($currentSubscribers as $userId) {
            if (isset($existingSubscribers[$userId]) && !$existingSubscribers[$userId]->is_active) {
                // Восстанавливаем активность пользователя
                DB::table('subscribers')
                    ->where('user_id', $userId)
                    ->update(['is_active' => true, 'updated_at' => now()]);
            }
        }

        // Находим отписавшихся подписчиков
        $removedSubscribers = array_diff(array_keys($existingSubscribers), $currentSubscribers);

        // Помечаем отписавшихся пользователей как неактивных
        if (!empty($removedSubscribers)) {
            DB::table('subscribers')
                ->whereIn('user_id', $removedSubscribers)
                ->update(['is_active' => false, 'updated_at' => now()]);
        }

        // Добавляем новых подписчиков
        foreach ($newSubscribers as $userId) {
            DB::table('subscribers')->updateOrInsert(
                ['user_id' => $userId], // Идентификатор подписчика
                [
                    'is_active' => true,
                    'subscribed_at' => now(),
                    'updated_at' => now()
                ] // Данные для обновления
            );
        }
    }


    /**
     * Получение списка участников Telegram-канала.
     */
    public function getAllChannelParticipants($channelId)
    {
        $allParticipants = [];
        $offset = 0;
        $limit = 200;

        try {
            do {
                // Отправка запроса к Telegram API
                $response = $this->madeline->channels->getParticipants([
                    'channel' => $channelId,
                    'filter' => ['_' => 'channelParticipantsRecent'], // Получите всех актуальных подписчиков
                    'offset' => $offset,
                    'limit' => $limit,
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
}
