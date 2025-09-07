<?php

namespace App\Controllers;

use App\Services\LogService as Log;
use App\Services\Tasks\CreateTaskService;
use Pecee\SimpleRouter\SimpleRouter as Router;

class CreateTaskController
{

    public function execute(): bool
    {
        $params = Router::request()->getInputHandler()->all();

        $ct_service = new CreateTaskService();
        $task_id = $ct_service::get_task_id($params);

        if (is_null($task_id)) {
            Log::info("Некорректный запрос", $params);
            return false;
        }

        // Проверяем, что задачу создал именно наш бот
        $exist = $ct_service->task_exist($task_id);
        if (!$exist) {
            Log::info('Эта задача создана не нашим ботом', ['task_id' => $task_id]);
            return false;
        }

        // Получаем задачу из Б24
        $bx_task = $ct_service->get_bx_task($task_id);

        if (is_null($bx_task) || empty($bx_task['responsibleId'])) {
            Log::info(
                "Задача не найдена в Б24 или отсутствует ответственный",
                ['task_id' => $task_id, 'task' => $bx_task]
            );
            return false;
        }

        // Получаем ответственного за задачу
        $bx_user = $ct_service->get_bx_user($bx_task['responsibleId']);

        if (empty($bx_user)) {
            Log::info("Пользователь не найден в CRM", ['task' => $bx_task, 'user' => $bx_user]);
            return false;
        }

        // Отправляем оповещение в общий чат
        $ct_service->send_chat_notify($bx_task, $bx_user);

        return true;
    }

}
