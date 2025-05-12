<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    // Обработка входящих обновлений от Telegram
    public function handleWebhook(Request $request)
    {
        // Получаем все данные обновления от Telegram
        $update = $request->all();

        // Логируем обновления для отладки
        Log::info('Telegram Update: ', $update);

        /*Пока ля теста */
        $chatId = $update['channel_post']['chat']['id']; // Корректно
        $this->sendMessage($chatId, "Тест Добро пожаловать, {$chatId}!");
        /** */


        // Проверяем наличие массива 'new_chat_members' для отслеживания новых членов
        if (isset($update['message']['new_chat_members'])) {
            foreach ($update['message']['new_chat_members'] as $newMember) {
                $chatId = $update['message']['chat']['id'];
                $userId = $newMember['id'];
                $firstName = isset($newMember['first_name']) ? $newMember['first_name'] : 'Пользователь';

                Log::info('New subscriber ID: ' . $userId);

                $this->sendToYandexMetrica($userId, $firstName);
                $this->sendMessage($chatId, "Добро пожаловать, {$firstName}!");
            }
        }

        return response()->json(['status' => 'ok']); // Возвращаем успешный ответ
    }

    private function sendMessage($chatId, $text)
    {
        // Укажите токен вашего бота из .env
        $token = env('TELEGRAM_BOT_TOKEN');

        // Формируем URL для отправки сообщения
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        // Отправляем запрос через Telegram API
        $response = Http::post($url, [
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        // Логируем, чтобы отлаживать ошибки
        if ($response->failed()) {
            Log::error('Ошибка отправки сообщения в Telegram:', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } else {
            Log::info('Сообщение успешно отправлено в Telegram:', $response->json());
        }
    }

    private function sendToYandexMetrica($userId, $userName)
    {
        // Укажите ваш ID счетчика и ID цели
        $counterId = '101725200';  // Замените на свой ID счетчика
        $goalId = 'new_subscriber';          // Замените на свой ID цели

        // URL для отправки данных в Яндекс Метрику
        $url = "https://mc.yandex.ru/watch/{$counterId}";

        // Параметры для отправки
        $data = [
            't' => 'goal',                 // Тип отправляемых данных
            'id' => $goalId,               // ID цели
            'uid' => $userId,              // ID пользователь (можно оставить пустым если не нужно)
            'url' => 'https://t.me/+GBRMKva5zohjNjMy', // URL вашего канала
            'g' => 'new_subscriber',        // Уникальный идентификатор события
            'name' => $userName,           // Передаем имя пользователя

        ];

        // Подготовка запроса
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data), // Подготовка данных
            ],
        ];

        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context); // Выполнение запроса

        // Логируем ответ для отладки
        Log::info('Response from Yandex Metrica: ', ['response' => $response]);
    }
}
