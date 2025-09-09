<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class TelegramService
{
    public const API_URL = 'https://api.telegram.org/bot';

    public function send_corp_chat_notify(string $message, string $task_url): void
    {
        try {
            $res = $this->request_notify_bot(
                $message,
                [
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                    'reply_markup' => json_encode(
                        ['inline_keyboard' => [[['text' => '🌐 Перейти', 'url' => $task_url]]]],
                        256
                    )
                ]
            );
        } catch (\JsonException $e) {
            LogService::error(
                "Ошибка при отправке оповещения в корп чат!\n" . $e->getMessage(),
                ['message' => $message]
            );
        }
    }

    /**
     * Отправка сообщений через бота для уведомлений. Вероятно @fbm_notify_bot
     * @param string $text
     * @param array $additional
     * @return array
     * @throws \JsonException
     */
    public function request_notify_bot(string $text, array $additional = []): array
    {
        $url = self::API_URL . env('TG_NOTIFY_BOT', 'token');
        $response = $this->request($url . '/sendMessage', [
            'chat_id' => env('TG_NOTIFY_CHAT'),
            'text' => $text,
            ...$additional
        ]);

        if (isset($response['error'])) {
            LogService::error('Error: ' . $response['error']);
        }

        return $response;
    }

    /**
     * Отправка сообщений через бота для создания задач. Вероятно @fbm_task_bot
     * @param string $text
     * @param array $additional
     * @return array
     * @throws \JsonException
     */
    public function request_task_bot(array $params): array
    {
        $url = self::API_URL . env('TG_TASK_BOT', 'token');

        $method = isset($params['media']) ? 'sendMediaGroup' : 'sendMessage';

        $response = $this->request($url . '/' . $method, $params);

        if (
            !$response['ok'] &&
            isset($response['description']) &&
            str_contains($response['description'], 'message to be replied not found')
        ) {
            unset($params['reply_to_message_id']);
            $response = $this->request($url . '/' . $method, $params);
        }

        if (isset($response['error'])) {
            LogService::error(
                'Error: ' . $response['error'],
                ['params' => $params]
            );
        }

        return $response;
    }

    private function request(string $url, array $params): array
    {
        try {
            $response = (new Client())->post($url, ['json' => $params]);

            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if ($statusCode !== 200) {
                LogService::error('Ошибка при отправке данных в Telegram', [
                    'code' => $statusCode,
                    'content' => $content,
                    'params' => $params,
                ]);
            }

            return $content;
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if (!empty($content = $response->getBody()->getContents())) {
                LogService::error('Запрос:', ['content' => $content, 'params' => $params]);
                try {
                    return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    return ['ok' => false, 'error' => 'Ошибка ответа', 'error_description' => $content];
                }
            }

            LogService::error('Ошибка отправки данных в Телеграм', [
                'response' => $response,
                'e' => $e->getMessage(),
                'params' => $params,
            ]);
        } catch (JsonException|GuzzleException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        return ['ok' => false, 'error' => 'Непредвиденная ошибка', 'params' => $params];
    }
}
