<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Services\TelegramService;

class TelegramController extends Controller
{
    private $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Обработка входящих сообщений от Telegram.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */








    /* public function handleWebhook(Request $request)
    {


        $update = $request->all(); // Получаем данные от Telegram
        Log::info('Telegram Webhook Data:', $update); // Логируем данные для отладки

        // Проверяем, что это сообщение из канала
        if (isset($update['channel_post'])) {
            $channelPost = $update['channel_post']; // Берем данные из channel_post

            // Определяем ID канала и отправителя
            $channelId = $channelPost['chat']['id'];      // ID канала
            $senderId = $channelPost['sender_chat']['id']; // ID отправителя (обычно совпадает с каналом)

            // Если отправителем является сам канал, игнорируем сообщение
            if ($senderId === $channelId) {
                Log::info("Сообщение отправлено каналом (ботом), оно не будет обработано.");
                return response()->json(['status' => 'ignored']);
            }

            // Проверяем, есть ли текст в сообщении
            if (isset($channelPost['text'])) {
                $text = $channelPost['text']; // Текст сообщения

                // Отправляем приветствие
                try {
                    $this->telegramService->sendMessage($channelId, 'Привет!');
                } catch (\Exception $e) {
                    Log::error("Ошибка отправки сообщения в канал: " . $e->getMessage());
                }
            }
        }

        return response()->json(['status' => 'ok']); // Уведомляем Telegram, что запрос обработан



    } */



    public function handleWebhook(Request $request)
    {
        $update = $request->all();
        Log::info('Telegram Webhook Data:', $update); // Логируем всю активность

        // Обработка команды /start от пользователя
        if (isset($update['message'])) {
            $message = $update['message'];

            // Получение информации о чате
            $chat = $message['chat'];
            $chatId = $chat['id']; // Telegram ID пользователя
            $firstName = $chat['first_name'] ?? '';
            $username = $chat['username'] ?? '';

            // Проверка на команду /start
            if (isset($message['text']) && $message['text'] === '/start') {

                // Логируем пользователя
                Log::info("Новый пользователь: $chatId ($username)");

                // Сохраняем ID пользователя, если нужно
                // UserTelegram::create([...]);

                // Ссылка на канал
                $channelUrl = 'https://t.me/+GBRMKva5zohjNjMy';

                // Отправка приветственного сообщения с кнопкой
                /* $this->telegramService->sendMessageWithKeyboard($chatId, 'Привет! Подпишись на наш Telegram-канал:', [
                    [
                        ['text' => '📢 Подписаться на канал', 'url' => $channelUrl]
                    ]
                ]); */

                $this->telegramService->sendMessageWithKeyboard($chatId, 'Подпишись на канал, чтобы получать обновления:', [
                    [
                        ['text' => '📢 Подписаться на канал', 'url' => 'https://t.me/+GBRMKva5zohjNjMy']
                    ]
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
