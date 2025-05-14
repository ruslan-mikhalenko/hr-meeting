<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService; // Ваш сервис для работы с Telegram

class CheckTelegramSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:check-subscribers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check new subscribers in the Telegram channel and send data to Yandex Metrica';

    /**
     * TelegramService instance.
     */
    protected $telegramService;

    /**
     * Create a new command instance.
     */
    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();

        $this->telegramService = $telegramService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        /*-1002600859664 */
        $channelId = '@komplemir_by'; // Замените на ID вашего канала

        // Вызов метода отслеживания подписчиков
        $this->telegramService->trackNewSubscribers($channelId);

        $this->info('Подписчики проверены и отправлены в Метрику!');
    }
}
