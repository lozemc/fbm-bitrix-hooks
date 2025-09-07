<?php

namespace App\Services\Tasks;

use App\Models\BitrixTask;
use App\Services\BitrixService;
use App\Services\TelegramService;

class CreateTaskService
{
    private BitrixService $bx;
    private TelegramService $tg;

    public function __construct()
    {
        $this->bx = new BitrixService();
        $this->tg = new TelegramService();
    }

    public static function get_task_id(array $params = [])
    {
        if (!isset($params['event']) || $params['event'] !== 'ONTASKADD') {
            return null;
        }

        if (!isset($params['data']) || empty($params['data']['FIELDS_AFTER'])) {
            return null;
        }

        return $params['data']['FIELDS_AFTER']['ID'] ?? null;
    }

    public function task_exist(string $task_id): bool
    {
        // ждем чтобы бот точно создал запись о задаче в БД
        sleep(4);

        $task = BitrixTask::where('task_id', $task_id)->first();
        return !is_null($task);
    }

    public function get_bx_task(string $task_id)
    {
        return $this->bx->getTask($task_id);
    }

    public function get_bx_user(string $user_id)
    {
        return $this->bx->getUser($user_id)['result'][0] ?? null;
    }

    public function send_chat_notify(array $bx_task, array $bx_user): void
    {
        [$message, $task_url] = $this->get_notify_params($bx_task, $bx_user);

        $this->tg->send_new_task_notify($message, $task_url);
    }

    private function get_notify_params(array $task, array $user): array
    {
        $task_url = 'https://' . env('BX_HOST') . "/company/personal/user/{$user['ID']}/tasks/task/view/{$task['id']}/";

        $full_name = trim($user['NAME'] . ' ' . $user['LAST_NAME']);

        $username = $user[env('BX_TG_USERNAME_FIELD', 'UF_USR_XX')] ?? '';
        $username = preg_replace('/@/', '', $username);

        $title = trim(mb_substr($task['title'], 0, 30));
        if ($title !== $task['title']) {
            $title .= '...';
        }

        $message = sprintf(
            "🎉 Создана новая <a href='%s'>задача</a>\n" .
            "├ <b>Название:</b> %s\n" .
            "├ <b>Ответственный:</b> %s\n" .
            '└ <b>Username:</b> %s',
            $task_url,
            $title,
            !empty($full_name) ? $full_name : '-',
            !empty($username) ? "@{$username}" : "-\n\n🚨️Обратите внимание, <b>не указан</b> username у пользователя"
        );

        return [$message, $task_url];
    }


}
