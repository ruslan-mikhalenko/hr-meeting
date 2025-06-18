<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService; // Ваш сервис для работы с Telegram
use Illuminate\Support\Facades\DB; // Для работы с БД

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
        // Получить все проекты из таблицы projects
        $projects = DB::table('projects')
            ->where('is_active', 1)
            ->select('link')->get();

        if ($projects->isEmpty()) {
            $this->error('В таблице projects нет ссылок.');
            return;
        }

        foreach ($projects as $project) {
            // Убедимся, что поле link не пустое
            if (!empty($project->link)) {
                $this->info("Проверка наличия проекта: {$project->link}");

                // Вызов метода отслеживания подписчиков
                $this->telegramService->trackNewSubscribers($project->link);

                // Пауза между обработкой проектов (например, 2 секунды)
                sleep(5);
            } else {
                $this->warn('Пропущено: проект с пустой ссылкой');
            }
        }

        $this->info('Все подписчики проверены и отправлены в Метрику!');
    }
}
