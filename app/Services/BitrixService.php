<?php

namespace App\Services;

use Lozemc\B24;

class BitrixService extends B24
{
    public function __construct()
    {
        parent::__construct(env('BX_USER'), env('BX_HOST'), env('BX_TOKEN'));
    }

    public function getTask($id)
    {
        return $this->request('tasks.task.get', [
            'taskId' => $id,
            'select' => ['*']
        ])['result']['task'] ?? null;
    }
}
