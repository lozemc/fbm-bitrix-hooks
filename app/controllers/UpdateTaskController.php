<?php

namespace App\Controllers;

use App\sources\Bitrix24Source;

class UpdateTaskController extends Bitrix24Source
{

    public function execute(){

        return json_encode([
            'status' => 'success',
            'class' => __CLASS__,
            'method' => 'execute'
        ], 256);
    }

}
