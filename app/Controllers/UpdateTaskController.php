<?php

namespace App\Controllers;

use App\Services\BitrixService;
use Pecee\SimpleRouter\SimpleRouter as Router;

class UpdateTaskController
{
    public function execute(): false|string
    {
        $task_id = $this->getTaskId();

        if (is_null($task_id)) {
            return 'fail';
        }

        // Проверяем задачу в нашей базе
        // TODO

        // Проверяем статус задачи из B24
        $task = (new BitrixService())->getTask($task_id);

        if (is_null($task)) {
            return 'fail';
        }

        # Если задача завершена
        if ($task['status'] === 5) {
            // Отправляем оповещение в чат
            // TODO


            // Закрываем задачу в бд
            // TODO
        }

        return 'ok';
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
