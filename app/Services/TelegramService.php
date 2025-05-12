<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Exception;

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

    public function getAllChannelParticipants(string $channelUsername): array
    {
        $allParticipants = []; // Массив для всех участников
        $offset = 0;
        $limit = 100; // Максимальное число пользователей за один запрос (ограничение Telegram API)

        try {
            do {
                // Получаем участников с текущим оффсетом
                $response = $this->madeline->channels->getParticipants([
                    'channel' => $channelUsername,
                    'filter' => ['_' => 'channelParticipantsRecent'], // Используем стандартный фильтр
                    'offset' => $offset,
                    'limit' => $limit,
                ]);

                // Проверяем, есть ли участники в текущем запросе
                if (isset($response['participants']) && count($response['participants']) > 0) {
                    foreach ($response['participants'] as $participant) {
                        // Добавляем участников с указанием даты присоединения
                        $allParticipants[] = [
                            'user_id' => $participant['user_id'] ?? null,                    // ID пользователя
                            'role' => $participant['_'] ?? 'member',                        // Роль (например, admin, member)
                            'join_date' => isset($participant['date'])                       // Преобразуем дату присоединения

                                ? date('Y-m-d H:i:s', $participant['date']) // Форматируем в человекочитаемую строку
                                : null,
                        ];
                    }

                    $offset += $limit; // Увеличиваем оффсет для следующего запроса
                } else {
                    break; // Выходим из цикла, если больше участников нет
                }
            } while (count($response['participants']) > 0);
        } catch (\Exception $e) {
            logger()->error("Ошибка получения всех участников: " . $e->getMessage());
        }

        return $allParticipants;
    }
}
