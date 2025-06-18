<?php

namespace App\Http\Controllers;

use App\Models\Project;
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

                        $id = $parsed['id'] ?? null;
                        $orderId = $parsed['order'] ?? null;
                        $channel = $parsed['channel'] ?? null;


                        // Разделяем строку по дефису
                        $parts = explode('-', $id);

                        // Получаем вторую часть — это id проекта
                        $progect_id = $parts[1];

                        $project = Project::where('id', $progect_id)->first();



                        Log::info("Пользователь пришёл с сайта: user=$id");

                        /* $messageText = "<b>Добро пожаловать в наш канал - {$project->name}!</b>\n\n"
                            . "📌 Здесь вы найдёте:\n"
                            . "• 🔥 Актуальные новости\n"
                            . "• 📈 Обновления сервиса\n"
                            . "• 🎁 Эксклюзивные предложения\n\n"
                            . "👇 <b>Нажмите кнопку ниже, чтобы подписаться:</b>"; */


                        $messageText = "<b>Добро пожаловать в наш канал — {$project->name}!</b>\n\n"
                            . "📌 <b>Здесь вы найдёте:</b>\n"
                            . "⠀\n" // отступ (U+2800 символ, выглядит как пустая строка)
                            . "🔹 {$project->about}\n"
                            . "⠀\n"
                            /* . "🚀 <i>Подпишитесь, чтобы не пропустить самое интересное!</i>" */
                            . "⠀\n"
                            . "👇 <b>Нажмите кнопку ниже, чтобы подписаться:</b>";

                        $this->telegramService->sendMessageWithKeyboard(
                            $chatId,
                            $messageText,
                            [
                                [
                                    ['text' => '📢 Подписаться на канал', 'url' => $project->link]
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
