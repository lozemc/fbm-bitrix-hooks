<?php

namespace App\Services\Tasks;

use App\Models\BitrixTask;
use App\Services\BitrixService;
use App\Services\TelegramService;
use App\Services\TranslateService;

class UpdateTaskService
{
    private BitrixService $bx;

    public function __construct()
    {
        $this->bx = new BitrixService();
    }

    public static function get_task_id($params)
    {
        if (!isset($params['event']) || $params['event'] !== 'ONTASKUPDATE') {
            return null;
        }

        if (!isset($params['data']) || empty($params['data']['FIELDS_AFTER'])) {
            return null;
        }

        return $params['data']['FIELDS_AFTER']['ID'] ?? null;
    }

    public function get_local_task($task_id): ?BitrixTask
    {
        return BitrixTask::with(['message', 'logs'])
            ->where('task_id', $task_id)
            ->where('status', 'pending')->first();
    }

    public function get_bx_task($task_id): mixed
    {
        return $this->bx->getTask($task_id);
    }

    public function actualize_status(BitrixTask $local_task, array $bx_task): void
    {
        $old_status = $local_task->logs?->last()?->new_status;
        $current_status = self::get_text_status($bx_task['status']);

        if ($old_status !== $current_status) {
            $local_task->logs()->create([
//                'task_id' => $task_id, // TODO check me
                'new_status' => $current_status,
                'old_status' => $old_status,
            ]);
        }
    }

    public function send_chat_notify(BitrixTask $local_task, array $bx_task): void
    {
        [$result_msg, $files] = $this->bx->getResultTask($bx_task['id']);

        $caption = self::get_final_message($local_task, $result_msg);

        $this->send_to_telegram($local_task, $caption, $files);
    }

    public function close_task(BitrixTask $local_task): void
    {
        $local_task->status = 'success';
        $local_task->save();
    }

    private function send_to_telegram(BitrixTask $local_task, string $caption, array $files = []): void
    {
        $tg_service = new TelegramService;
        if (!empty($files)) {
            if (mb_strlen($caption) > 1024) {
                $res = $tg_service->request_task_bot([
                    'chat_id' => $local_task->chat_id,
                    'media' => json_encode($files, JSON_UNESCAPED_UNICODE),
                    'reply_to_message_id' => $local_task->message_id,
                ]);

                $res2 = $tg_service->request_task_bot([
                    'chat_id' => $local_task->chat_id,
                    'text' => $caption,
                    'reply_to_message_id' => $local_task->message_id,
                    'parse_mode' => 'html',
                ]);
            } else {
                $files[0]['caption'] = $caption;

                $res = $tg_service->request_task_bot([
                    'chat_id' => $local_task->chat_id,
                    'media' => json_encode($files, JSON_UNESCAPED_UNICODE),
                    'reply_to_message_id' => $local_task->message_id,
                ]);
            }
        } else {
            $res = $tg_service->request_task_bot([
                'chat_id' => $local_task->chat_id,
                'text' => $caption,
                'reply_to_message_id' => $local_task->message_id,
                'parse_mode' => 'html'
            ]);
        }
    }

    private static function get_final_message(BitrixTask $local_task, string $result_msg): string
    {
        $lang = TranslateService::get_lang($local_task->chat_id);

        if ($result_msg !== '') {
            $result_msg = $lang->result_message . "\n\n" . $result_msg;
        }

        $caption = $lang->complete_task . "\n\n" . $result_msg;

        if (!empty($local_task->message->notify)) {
            $caption = '@' . $local_task->message->notify . "\n\n" . $caption;
        }

        return $caption;
    }

    private static function get_text_status(string $status_id): string
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

}
