<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

use App\Services\TelegramService; // Подключение класса TelegramService

class SubscriberController extends Controller
{
    public function index()
    {

        $telegramService = new TelegramService();
        try {
            // Получаем всех участников канала
            /**-1002600859664 это id закрытого канала @kaktotak_by @silaKnig*/
            $participants = $telegramService->getAllChannelParticipantsReally('https://t.me/silaKnig');

            // Отладки: вывод структуры участников
            dd($participants);
        } catch (\Exception $e) {
            // Логируем ошибку в случае проблемы
            logger()->error('Ошибка: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка: ' . $e->getMessage()]);
        }

        // Получаем новые подписки из кэша
        $subscribers = Cache::get('telegram_channel_participants', []);

        return Inertia::render('Subscribers', [
            'subscribers' => $subscribers,
        ]);
    }
}
