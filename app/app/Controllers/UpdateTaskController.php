<?php

namespace App\Controllers;

use App\Services\LogService;
use App\Services\Tasks\UpdateTaskService;
use Pecee\SimpleRouter\SimpleRouter as Router;

class UpdateTaskController
{
    public function execute()
    {
        $params = Router::request()->getInputHandler()->all();

        $ut_service = new UpdateTaskService();
        $task_id = $ut_service::get_task_id($params);

        if (is_null($task_id)) {
            LogService::warning('Некорректный запрос', $params);
            return false;
        }

        // Ищем задачу в нашей БД и проверяем не закрыта ли она
        $local_task = $ut_service->get_local_task($task_id);

        if (!$local_task) {
            LogService::info("Задачи нет в БД или она уже завершена", ['task_id' => $task_id]);
            return true;
        }

        // Ищем задачу в B24
        $bx_task = $ut_service->get_bx_task($task_id);

        if (empty($bx_task)) {
            LogService::warning(
                "Не удалось получить информацию по задаче из Б24",
                ['task_id' => $task_id, 'task' => $bx_task],
            );
            return false;
        }

        // Актуализируем статус задачи в БД
        $ut_service->actualize_status($local_task, $bx_task);

        // Если задача завершена
        if ((int)$bx_task['status'] === 5) {

            // Отправляем оповещение в чат
            $ut_service->send_chat_notify($local_task, $bx_task);

            // Закрываем задачу в бд
            $ut_service->close_task($local_task);
        }

        return true;
    }
}
