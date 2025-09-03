<?php

namespace App\controllers;

use App\sources\Bitrix24Source;

class CreateTaskController extends Bitrix24Source
{

    public function execute(){
        return json_encode([
            'status' => 'success',
            'class' => __CLASS__,
            'method' => 'execute'
        ], 256);
    }

}
