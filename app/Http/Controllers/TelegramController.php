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


    public function handleWebhook(Request $request)
    {
        $update = $request->all();
        Log::info('Telegram Webhook Data:', $update); // Логируем всю активность

        if (isset($update['message'])) {
            $message = $update['message'];
            $chat = $message['chat'];
            $chatId = $chat['id'];
            $firstName = $chat['first_name'] ?? '';
            $username = $chat['username'] ?? '';

            if (isset($message['text']) && str_starts_with($message['text'], '/start')) {
                $text = $message['text'];
                $params = explode(' ', $text);
                $payload = $params[1] ?? null;

                Log::info("Параметр после /start: " . ($payload ?? 'нет'));

                if ($payload) {
                    try {
                        $decoded = base64_decode($payload);
                        $decoded = urldecode($decoded); // обязательно, чтобы получить строку с Юникодом

                        // Преобразуем в query-форму для parse_str
                        parse_str(str_replace('|', '&', $decoded), $parsed);

                        $userId = $parsed['user'] ?? null;
                        $orderId = $parsed['order'] ?? null;
                        $channel = $parsed['channel'] ?? null;


                        Log::info("Пользователь пришёл с сайта: user=$userId, order=$orderId");

                        $messageText = "<b>Добро пожаловать в наш канал - {$channel}!</b>\n\n"
                            . "📌 Здесь вы найдёте:\n"
                            . "Тест - {$orderId} {$orderId}\n"
                            . "• 🔥 Актуальные новости\n"
                            . "• 📈 Обновления сервиса\n"
                            . "• 🎁 Эксклюзивные предложения\n\n"
                            . "👇 <b>Нажмите кнопку ниже, чтобы подписаться:</b>";

                        $this->telegramService->sendMessageWithKeyboard(
                            $chatId,
                            $messageText,
                            [
                                [
                                    ['text' => '📢 Подписаться на канал', 'url' => 'https://t.me/+GBRMKva5zohjNjMy']
                                ]
                            ],
                            'HTML' // ← Вот здесь ключ
                        );
                    } catch (\Exception $e) {
                        Log::error("Ошибка обработки payload: " . $e->getMessage());
                        $this->telegramService->sendMessage($chatId, 'Произошла ошибка. Пожалуйста, попробуйте позже.');
                    }
                } else {
                    $this->telegramService->sendMessage($chatId, 'Привет! Напиши /help, чтобы узнать команды.');
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
