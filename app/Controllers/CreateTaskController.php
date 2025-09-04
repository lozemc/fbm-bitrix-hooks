<?php

namespace App\Controllers;

use App\Services\BitrixService;
use Pecee\SimpleRouter\SimpleRouter as Router;

class CreateTaskController extends BitrixService
{

    public function execute(): false|string
    {
        $task_id = $this->getTaskId();

        if (is_null($task_id)) {
            return 'fail';
        }

        $service = new BitrixService();

        // Проверяем ответственного за задачу в B24
        $task = $service->getTask($task_id);

        if (is_null($task) || empty($task['responsibleId'])) {
            return 'fail';
        }

        # Получаем ответственного за задачу
        $user = $service->getUser($task['responsibleId'])['result'][0] ?? null;

        # Отправляем оповещение в общий чат
        // TODO


        return 'ok';
    }

    private function getTaskId()
    {
        $params = Router::request()->getInputHandler()->all();

        if (!isset($params['event']) || $params['event'] !== 'ONTASKADD') {
            return null;
        }

        if (!isset($params['data']) || empty($params['data']['FIELDS_AFTER'])) {
            return null;
        }

        return $params['data']['FIELDS_AFTER']['ID'] ?? null;
    }

}
