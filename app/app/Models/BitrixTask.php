<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitrixTask extends Model
{
    protected $table = 'bitrix_tasks';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function getMessageAttribute()
    {
        if (!isset($this->attributes['message'])) {
            $this->attributes['message'] = Message::where('chat_id', $this->chat_id)
                ->where('message_id', $this->message_id)
                ->first();
        }
        return $this->attributes['message'];
    }

    public function loadMessage()
    {
        $this->message = Message::where('chat_id', $this->chat_id)
            ->where('message_id', $this->message_id)
            ->first();
        return $this;
    }

    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BitrixTaskStatusLog::class, 'task_id', 'task_id');
    }
}
