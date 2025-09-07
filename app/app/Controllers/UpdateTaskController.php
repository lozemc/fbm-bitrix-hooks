<?php

namespace App\Controllers;

use App\Models\BitrixTask;
use App\Services\BitrixService;
use App\Services\LanguageService;
use App\Services\LogService;
use App\Services\TelegramService;
use Pecee\SimpleRouter\SimpleRouter as Router;

class UpdateTaskController
{
    public function execute()
    {
        $task_id = $this->getTaskId();

        if (is_null($task_id)) {
            LogService::warning('ID задачи не найден в запросе', Router::request()->getInputHandler()->all());
            return false;
        }

        // Проверяем задачу в нашей базе и она еще пока не закрыта
        $local_task = BitrixTask::with(['message', 'logs'])
            ->where('task_id', $task_id)
            ->where('status', 'pending')->first();

        if (!$local_task) {
            LogService::info("Задачи нет в БД или она уже завершена", ['task_id' => $task_id]);
            return true;
        }


        $bx_service = new BitrixService();

        // Проверяем статус задачи из B24
        $bx_task = $bx_service->getTask($task_id);

        if (empty($bx_task)) {
            LogService::warning("Не удалось получить информацию из Б24 по задаче c ID {$task_id}", ['task' => $bx_task]
            );
            return false;
        }


        $old_status = $local_task->logs?->last()?->new_status;
        $current_status = $this->getTextStatus($bx_task['status']);

        if ($old_status !== $current_status) {
            $local_task->logs()->create([
                'task_id' => $task_id,
                'new_status' => $current_status,
                'old_status' => $old_status,
            ]);
        }

        # Если задача завершена
        if ((int)$bx_task['status'] === 5) {
            // Отправляем оповещение в чат
            $lang = LanguageService::get_lang($local_task->chat_id);

            [$result_msg, $files] = $bx_service->getResultTask($bx_task['id']);

            if ($result_msg !== '') {
                $result_msg = $lang->result_message . "\n\n" . $result_msg;
            }

            $caption = $lang->complete_task . "\n\n" . $result_msg;

            if (!empty($local_task->message->notify)) {
                $caption = '@' . $local_task->message->notify . "\n\n" . $caption;
            }

            $tg_service = new TelegramService;
            if (!empty($files)) {
                if (mb_strlen($caption) > 1024) {
                    $tg_service->request_task_bot(
                        [
                            'chat_id' => $local_task->chat_id,
                            'media' => json_encode($files, JSON_UNESCAPED_UNICODE),
                            'reply_to_message_id' => $local_task->message_id,
                        ]
                    );

                    $tg_service->request_task_bot([
                        'chat_id' => $local_task->chat_id,
                        'text' => $caption,
                        'reply_to_message_id' => $local_task->message_id,
                        'parse_mode' => 'html',
                    ]);
                } else {
                    $files[0]['caption'] = $caption;

                    $tg_service->request_task_bot(
                        [
                            'chat_id' => $local_task->chat_id,
                            'media' => json_encode($files, JSON_UNESCAPED_UNICODE),
                            'reply_to_message_id' => $local_task->message_id,
                        ]
                    );
                }
            } else {
                $tg_service->request_task_bot(
                    [
                        'chat_id' => $local_task->chat_id,
                        'text' => $caption,
                        'reply_to_message_id' => $local_task->message_id,
                        'parse_mode' => 'html'
                    ]
                );
            }

            // Закрываем задачу в бд
            $local_task->status = 'success';
            $local_task->save();

            return true;
        }

        return true;
    }

    private function getTextStatus($status_id): string
    {
        $statuses = [
            '1' => 'Новая',
            '2' => 'Ожидает выполнения',
            '3' => 'В работе',
            '4' => 'Ожидает контроля',
            '5' => 'Завершена',
            '6' => 'Отложена',
            '7' => 'Отклонена',
            '0' => 'Задача удалена',
        ];

        return $statuses[$status_id] ?? "ID {$status_id}";
    }

    private function getTaskId()
    {
        $params = Router::request()->getInputHandler()->all();

        if (!isset($params['event']) || $params['event'] !== 'ONTASKUPDATE') {
            return null;
        }

        if (!isset($params['data']) || empty($params['data']['FIELDS_BEFORE'])) {
            return null;
        }

        return $params['data']['FIELDS_BEFORE']['ID'] ?? null;
    }
}
