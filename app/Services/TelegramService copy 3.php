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
            $this->madeline = new API($this->sessionPath);

            // Авторизация с использованием токена бота
            $this->madeline->botLogin(env('TELEGRAM_BOT_TOKEN')); // Токен берётся из .env
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
            $this->initializeMadeline(); // Инициализация MadelineProto

            // Отправка сообщения
            $this->madeline->messages->sendMessage([
                'peer'    => $chatId, // Идентификатор чата (может быть ID или @username)
                'message' => $text,   // Текст сообщения
            ]);
        } catch (\Exception $e) {
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
            $this->initializeMadeline();

            // Получить участников канала, если бот является администратором
            $participants = $this->madeline->channels->getParticipants([
                'channel' => $channelUsername,
                'filter'  => ['_' => 'channelParticipantsRecent'], // Или другой фильтр
                'offset'  => 0,
                'limit'   => 100, // Получайте данные частями
            ]);

            return $participants['users'] ?? [];
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
            $this->initializeMadeline(); // Инициализация MadelineProto

            // Получение информации о канале
            return $this->madeline->getFullInfo([
                'peer' => $channelUsername,
            ]);
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
                ['is_active' => true, 'subscribed_at' => now(), 'updated_at' => now()]
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
            $metrikaService = app(YandexMetrikaService::class);

            $allParticipants = $this->getAllChannelParticipants($channelId);
            $currentSubscribers = array_column($allParticipants, 'user_id');

            if (empty($currentSubscribers)) {
                Log::info("Для канала {$channelId} не найдено подписчиков.");
                return;
            }

            $storedSubscribers = DB::table('subscribers')->pluck('user_id')->toArray();
            $newSubscribers = array_diff($currentSubscribers, $storedSubscribers);

            foreach ($newSubscribers as $subscriberId) {
                /* $clientId = random_int(100000000, 999999999); */
                $clientId = $subscriberId;
                $metrikaService->sendEvent($clientId, 'new_subscriber', [
                    'url' => 'https://t.me/komplemir_by',
                ]);
            }

            $this->saveSubscribers($currentSubscribers);

            Log::info("Успешно обработаны новые подписчики для канала {$channelId}.");
        } catch (\Exception $e) {
            Log::error("Ошибка при обработке новых подписчиков для канала {$channelId}: {$e->getMessage()}");
        }
    }
}
