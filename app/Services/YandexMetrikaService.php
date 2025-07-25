<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YandexMetrikaService
{
    protected $endpoint;
    protected $counterId;
    protected $secret;

    public function __construct(string $link)
    {
        $this->endpoint = config('services.yandex_metrika.endpoint');
        $this->setProjectSettings($link);
    }

    /**
     * Получение настроек проекта из таблицы projects по его link.
     *
     * @param string $link
     * @return void
     */
    protected function setProjectSettings(string $link): void
    {
        // Ищем проект в базе данных по link
        $project = DB::table('projects')->where('link', $link)->first();

        if (!$project) {
            throw new \Exception("Проект с link '{$link}' не найден в таблице 'projects'.");
        }

        // Устанавливаем значения counter_id и secret из базы данных
        $this->counterId = $project->yandex_metric_id ?? null;
        $this->secret = $project->measurement_protocol_token ?? null;

        if (empty($this->counterId) || empty($this->secret)) {
            throw new \Exception("У проекта с link '{$link}' отсутствует counter_id или secret.");
        }
    }

    /**
     * Отправка события в Яндекс.Метрику через Measurement Protocol
     *
     * @param string $clientId Уникальный идентификатор клиента (cid)
     * @param string $eventAction Название цели (например, "new_subscriber")
     * @param array $params Дополнительные параметры
     * @return bool
     */
    public function sendEvent(string $clientId, string $eventAction, array $params = []): bool
    {
        // Если формат cid некорректен или не был передан, генерируем подходящий идентификатор
        if (empty($clientId)) {
            $clientId = uniqid(); // Генерируем уникальный идентификатор
        }

        // Текущая метка времени
        $eventTimestamp = time();

        // 1. Сначала отправляем PageView, чтобы создать визит
        $pageviewData = [
            'tid' => $this->counterId,
            'cid' => $clientId,
            't' => 'pageview',
            'dl' => $params['url'] ?? 'https://example.com',
            'dt' => 'Telemetry PageView',
            'et' => $eventTimestamp,
        ];

        if (!empty($this->secret)) {
            $pageviewData['ms'] = $this->secret;
        }

        $pageviewResponse = Http::asForm()->post($this->endpoint, $pageviewData);

        if (!$pageviewResponse->successful()) {
            Log::error("Ошибка отправки PageView в Яндекс.Метрику. Ответ: " . $pageviewResponse->body());
            return false;
        }

        Log::info("PageView успешно отправлен в Яндекс.Метрику (Client ID: {$clientId}).");

        // 2. Формируем запрос для Event
        $eventData = [
            'tid' => $this->counterId,
            'cid' => $clientId,
            't' => 'event',
            'ea' => $eventAction,
            'dl' => $params['url'] ?? 'https://example.com',
            'et' => $eventTimestamp,
        ];

        if (!empty($params['custom_params'])) {
            $eventData['params'] = $params['custom_params'];
        }

        if (!empty($this->secret)) {
            $eventData['ms'] = $this->secret;
        }

        // Отправляем Event запрос
        $eventResponse = Http::asForm()->post($this->endpoint, $eventData);
        /*  https: //mc.yandex.ru/collect/?tid=5564333&cid=1710232430899999999&t=pageview&dr=https%3A%2F%2Fyandex.ru&dl=https%3A%2F%2Ftest.com&dt=Test&et=1632467908&ms=01c0a669-d38c-4385-9d22-47cf894ed687
        https: //mc.yandex.ru/collect/?tid=5564333&cid=1710232430899999999&t=event&ea=order-success&et=1632467909&dl=https%3A%2F%2Ftest.com&ms=01c0a669-d38c-4385-9d22-47cf894ed687 */

        Log::info("Просмотр - https://mc.yandex.ru/collect/?tid={$this->counterId}&cid={$clientId}&t=pageview&dl={$params['url']}&dt='Telemetry PageView'&et={$eventTimestamp}&ms={$eventData['ms']}");
        Log::info("Событие - https://mc.yandex.ru/collect/?tid={$this->counterId}&cid={$clientId}&t=event&ea={$eventAction}&et={$eventTimestamp}&dl={$params['url']}&ms={$eventData['ms']}");

        if ($eventResponse->successful()) {
            Log::info("Событие '{$eventAction}' успешно отправлено в Яндекс.Метрику (Client ID: {$clientId}).");
            return true;
        }

        Log::error("Ошибка отправки события '{$eventAction}' в Яндекс.Метрику. Ответ: " . $eventResponse->body());
        return false;
    }
}
