<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;

class CheckTelegramSubscribers extends Command
{
    protected $signature = 'telegram:check-subscribers';
    protected $description = 'Check new Telegram channel subscribers';
    private $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle()
    {
        // Укажите username канала (например, @channel_username)
        $channelUsername = '+GBRMKva5zohjNjMy';

        // Получаем текущих участников канала
        $currentParticipants = $this->telegramService->getChannelParticipants($channelUsername);

        // Получаем сохранённое состояние участников из кэша
        $previousParticipants = Cache::get('telegram_channel_participants', []);

        // Сравниваем состояния для выявления новых подписчиков
        $newParticipants = array_diff_key($currentParticipants, $previousParticipants);

        foreach ($newParticipants as $participant) {
            // Проверяем массив это или объект
            $username = is_array($participant)
                ? ($participant['username'] ?? 'Unknown')    // Для массивов
                : ($participant->username ?? 'Unknown');    // Для объектов

            $this->info("New subscriber: {$username}");
        }

        // Сохраняем текущее состояние участников в кэше
        Cache::put('telegram_channel_participants', $currentParticipants, now()->addMinutes(10));
    }
}
