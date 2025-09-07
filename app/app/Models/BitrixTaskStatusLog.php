<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitrixTaskStatusLog extends Model
{
    protected $table = 'bitrix_task_status_log';
    protected $primaryKey = 'task_id';
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'old_status',
        'new_status',
    ];
}
