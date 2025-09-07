<?php

namespace App\Services;

use Lozemc\B24;

class BitrixService extends B24
{
    public function __construct()
    {
        parent::__construct(
            env('BX_USER', '1'),
            env('BX_HOST', 'test-host.bitrix24.ru'),
            env('BX_TOKEN', 'test-token')
        );
    }

    /**
     * Метод для получения данных по задаче
     * @param int $task_id
     * @return mixed
     */
    public function getTask(int $task_id): mixed
    {
        return $this->request('tasks.task.get', [
            'taskId' => $task_id,
            'select' => ['*']
        ])['result']['task'] ?? null;
    }

    /**
     * Метод для получения данных о финальном результате задачи
     * @param int $task_id
     * @return array
     */
    public function getResultTask(int $task_id): array
    {
        $result = $this->request('tasks.task.result.list', [
            'taskId' => $task_id,
            'select' => ['*']
        ])['result'][0] ?? null;

        $files = [];

        $message = !empty($result['formattedText']) ? $result['formattedText'] : '';
        $message = preg_replace('/<br ?\/?>/i', "\n", $message); // заменяем <br> и <br />
        $message = preg_replace('/&nbsp;|\t/', ' ', $message); // заменяем неразрывные пробелы и табы
        $message = strip_tags($message); // Удаляем все html сущности

        if (!empty($result['files'])) {
            foreach ($result['files'] as $file) {
                if (!empty($res = $this->getAttachedObject($file))) {
                    $files[] = $res;
                }
            }
        }

        if (!empty($files)) {
            // Если есть хотя бы один документ, то превращаем все файлы в документы (ограничение от телеграм)
            $docs = count(array_filter($files, static fn($item) => $item['type'] === 'document'));

            if ($docs && count($files) !== $docs) {
                $files = array_map(static function ($item) {
                    $item['type'] = 'document';
                    return $item;
                }, $files);
            }
        }

        return [$message, $files];
    }

    /**
     * Получение файлов из результата задачи
     * @param $file_id
     * @return array
     */
    private function getAttachedObject($file_id): array
    {
        $response = $this->request('disk.attachedObject.get', [
            'id' => $file_id,
        ])['result'] ?? [];

        $split = explode('.', $response['NAME']);
        $ext = $split[array_key_last($split)];

        $type = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp']) ? 'photo' : 'document';

        if (!empty($response)) {
            return [
                'type' => $type,
                'media' => $response['DOWNLOAD_URL'],
                'name' => $response['NAME'],
                'parse_mode' => 'HTML'
            ];
        }

        return [];
    }
}
