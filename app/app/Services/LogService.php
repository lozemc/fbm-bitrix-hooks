<?php

namespace App\Services;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * Class LogService
 *
 * @method static void emergency(string|\Stringable $message, array $context = [])
 * @method static void alert(string|\Stringable $message, array $context = [])
 * @method static void critical(string|\Stringable $message, array $context = [])
 * @method static void error(string|\Stringable $message, array $context = [])
 * @method static void warning(string|\Stringable $message, array $context = [])
 * @method static void notice(string|\Stringable $message, array $context = [])
 * @method static void info(string|\Stringable $message, array $context = [])
 * @method static void debug(string|\Stringable $message, array $context = [])
 * @method static void log($level, string|\Stringable $message, array $context = [])
 */
class LogService
{
    protected static ?Logger $logger = null;

    protected static function getLogger(): Logger
    {
        if (self::$logger === null) {
            $logger = new Logger('my_app');

            $logPath = '/var/log/app/app.log';

            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                if (!mkdir($logDir, 0775, true) && !is_dir($logDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDir));
                }
            }

            $dateFormat = 'Y-m-d H:i:s';
            $output = "[%datetime%] %level_name%: %message% %context%\n";
            $formatter = new LineFormatter($output, $dateFormat, true, true);

            $handler = new RotatingFileHandler($logPath, 30, Logger::DEBUG);
            $handler->setFilenameFormat('{filename}-{date}', 'Y-m-d');
            $handler->setFormatter($formatter);

            $logger->pushHandler($handler);
            self::$logger = $logger;
        }

        return self::$logger;
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     * @uses \Monolog\Logger
     */
    public static function __callStatic($method, $parameters)
    {
        $logger = self::getLogger();

        if (!method_exists($logger, $method)) {
            throw new \BadMethodCallException("Метод {$method} не найден в Logger");
        }

        if (isset($parameters[0]) && !is_string($parameters[0])) {
            $parameters[0] = is_scalar($parameters[0]) ? (string)$parameters[0]
                : json_encode($parameters[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $logger->$method(...$parameters);
    }
}
