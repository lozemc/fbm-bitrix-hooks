<?php

namespace App\Controllers;

use App\Services\BitrixService;
use App\Services\LogService as Log;
use App\Services\TelegramService;
use Pecee\SimpleRouter\SimpleRouter as Router;

class CreateTaskController extends BitrixService
{

    public function execute(): bool
    {
        $task_id = $this->getTaskId();

        if (is_null($task_id)) {
            Log::info("ID задачи не найден в запросе", Router::request()->getInputHandler()->all());
            return false;
        }

        $service = new BitrixService();

        // Проверяем ответственного за задачу в B24
        $task = $service->getTask($task_id);

        if (is_null($task) || empty($task['responsibleId'])) {
            Log::info("Задача не найдена в Б24 или отсутствует ответственный, ID $task_id", ['task' => $task]);
            return false;
        }

        # Получаем ответственного за задачу
        $user = $service->getUser($task['responsibleId'])['result'][0] ?? null;
        if (empty($user)) {
            Log::info(
                "Пользователь с ID {$task['responsibleId']} (задача $task_id) не найден",
                ['task' => $task, 'user' => $user]
            );
            return false;
        }

        # Отправляем оповещение в общий чат
        $tg_service = new TelegramService;
        $tg_service->sendNewTaskNotify($task, $user);

        return true;
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
