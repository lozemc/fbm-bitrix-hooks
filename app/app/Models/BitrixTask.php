<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitrixTask extends Model
{
    protected $table = 'bitrix_tasks';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function message(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Message::class, 'message_id', 'message_id');
    }

    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BitrixTaskStatusLog::class, 'task_id', 'task_id');
    }
}
