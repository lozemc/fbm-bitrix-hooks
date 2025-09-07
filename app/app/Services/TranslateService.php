<?php

namespace App\Services;

use App\Models\ChatSetting;
use RuntimeException;

class TranslateService
{
    public const TRANSLATIONS = [
        'ru' => [
            'get_error_processed' => "🥺 В процессе создания задачи произошла ошибка\nПопробуйте повторить операцию позже",
            'empty_task_message' => 'Не указан текст задачи',
            'complete_task' => '🥳 Задача завершена!',
            'task_created' => '✅ Задача успешно создана!',
            'result_message' => '<b>Комментарий по задаче</b>:',
        ],
        'en' => [
            'get_error_processed' => "🥺 An error occurred while creating the task\nTry the operation again later",
            'empty_task_message' => 'The task text is not specified',
            'complete_task' => '🥳 Task completed!',
            'task_created' => '✅ Task successfully created!',
            'result_message' => '<b>Comment on the task</b>:',
        ]
    ];

    private string $lang;

    public function __construct($lang = 'ru')
    {
        $this->set_lang($lang);
    }

    public function __get(string $name)
    {
        $translate = self::TRANSLATIONS[$this->lang][$name] ?? null;
        if (is_null($translate)) {
            throw new RuntimeException("Translate not found: {$name}");
        }

        return $translate;
    }

    public function __set(string $name, $value): void
    {
        // TODO: Implement __set() method.
    }

    public function __isset(string $name): bool
    {
        // TODO: Implement __isset() method.
    }

    public static function get_lang(string $chat_id)
    {
        $lang = ChatSetting::find($chat_id)?->first()?->lang ?? 'ru';
        return new TranslateService($lang);
    }

    public function set_lang($lang): void
    {
        if (!isset(self::TRANSLATIONS[$lang])) {
            throw new RuntimeException("Unsupported language: {$lang}");
        }

        $this->lang = $lang;
    }

}
