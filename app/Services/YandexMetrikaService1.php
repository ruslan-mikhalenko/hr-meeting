<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;

class YandexMetrikaService1
{
    protected $endpoint;
    protected $counterId;
    protected $secret;

    public function __construct()
    {
        $this->endpoint = config('services.yandex_metrika.endpoint');
        $this->counterId = config('services.yandex_metrika.counter_id');
        $this->secret = config('services.yandex_metrika.secret');
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

        // 1. Сначала отправляем PageView, чтобы создать визит (необязательно для вложенных параметров, но рекомендовано)
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

        // Если передан параметр params, добавляем его в запрос
        /*  if (!empty($params['custom_params'])) {
            // Преобразуем массив custom_params в JSON и кодируем в URL
            $eventData['params'] = json_encode($params['custom_params'], JSON_UNESCAPED_UNICODE);
        } */

        // Добавление параметра raw params (без JSON и URL кодирования)
        if (!empty($params['custom_params'])) {
            $eventData['params'] = $params['custom_params']; // Параметры передаются в сыром виде
        }

        if (!empty($this->secret)) {
            $eventData['ms'] = $this->secret;
        }

        // Отправляем Event запрос
        $eventResponse = Http::asForm()->post($this->endpoint, $eventData);

        if ($eventResponse->successful()) {
            Log::info("Событие '{$eventAction}' успешно отправлено в Яндекс.Метрику (Client ID: {$clientId}).");
            return true;
        }

        Log::error("Ошибка отправки события '{$eventAction}' в Яндекс.Метрику. Ответ: " . $eventResponse->body());
        return false;
    }
}
