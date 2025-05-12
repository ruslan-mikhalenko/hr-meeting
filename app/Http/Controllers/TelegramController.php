<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;

class TelegramController extends Controller
{
    private $telegramService;
    private $botId; // Хранение ID бота

    public function __construct(TelegramService $telegramService)
    {
        // Внедрим TelegramService через конструктор
        $this->telegramService = $telegramService;

        // Получаем ID бота при инициализации контроллера
        $this->botId = $this->telegramService->getBotId();
        Log::info("Идентификатор бота: {$this->botId}");
    }

    // Обработка входящих обновлений от Telegram
    public function handleWebhook(Request $request)
    {
        // Получаем все данные обновления от Telegram
        $update = $request->all();

        // Логируем обновления для отладки
        Log::info('Получено обновление Telegram: ', $update);

        // Проверяем наличие массива `channel_post` или `message`
        if (isset($update['channel_post'])) {
            $chatId = $update['channel_post']['chat']['id'];
            $this->sendMessage($chatId, "Добро пожаловать в канал, {$chatId}!");
        }

        // Проверяем наличие новых участников чата
        if (isset($update['message']['new_chat_members'])) {
            foreach ($update['message']['new_chat_members'] as $newMember) {
                $chatId = $update['message']['chat']['id'];
                $userId = $newMember['id'];
                $firstName = $newMember['first_name'] ?? 'Пользователь';

                // Игнорируем события, если это бот
                if ($userId == $this->botId) {
                    Log::info("Игнорируем событие, так как это бот: {$userId}");
                    continue;
                }

                Log::info("Новый подписчик с ID: {$userId}");

                // Ваши действия (например, отправка в Яндекс Метрику)
                $this->sendToYandexMetrica($userId, $firstName);
                $this->sendMessage($chatId, "Добро пожаловать, {$firstName}!");
            }
        }

        return response()->json(['status' => 'ok']); // Возвращаем успешный ответ
    }

    // Метод для отправки сообщений через TelegramService
    private function sendMessage($chatId, $text)
    {
        try {
            // Используем TelegramService для отправки сообщения
            $this->telegramService->sendMessage($chatId, $text);
            Log::info("Сообщение отправлено успешно: ChatID {$chatId}");
        } catch (\Exception $e) {
            Log::error("Ошибка отправки сообщения: {$e->getMessage()}");
        }
    }

    // Метод для отправки данных в Яндекс Метрику
    private function sendToYandexMetrica($userId, $userName)
    {
        // Укажите ваш ID счетчика и цель
        $counterId = '101725200';  // Замените на ваш ID счетчика
        $goalId = 'new_subscriber';  // Замените на вашу цель

        // URL для отправки данных в Яндекс Метрику
        $url = "https://mc.yandex.ru/watch/{$counterId}";

        // Параметры для отправки
        $data = [
            't' => 'goal',                 // Тип отправляемых данных
            'id' => $goalId,               // ID цели
            'uid' => $userId,              // ID пользователя
            'url' => 'https://t.me/+GBRMKva5zohjNjMy', // URL вашего канала
            'g' => 'new_subscriber',        // Уникальный идентификатор события
            'name' => $userName,           // Имя пользователя
        ];

        try {
            // Отправка данных в Яндекс Метрику
            $response = file_get_contents($url . '?' . http_build_query($data));
            Log::info('Отправка в Yandex Metrica: ', ['response' => $response]);
        } catch (\Exception $e) {
            Log::error("Ошибка отправки в Яндекс Метрику: {$e->getMessage()}");
        }
    }
}
