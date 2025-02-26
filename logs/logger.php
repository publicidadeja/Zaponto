<?php
class Logger {
    private static $logFile = 'claude_api.log';
    private static $logPath;

    public static function init() {
        self::$logPath = dirname(__FILE__) . '/logs/' . self::$logFile;
        
        if (!file_exists(dirname(__FILE__) . '/logs')) {
            mkdir(dirname(__FILE__) . '/logs', 0777, true);
        }
    }

    public static function log($message, $type = 'INFO') {
        if (!isset(self::$logPath)) {
            self::init();
        }

        $dateTime = date('Y-m-d H:i:s');
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }
        
        $logMessage = "[$dateTime][$type] $message" . PHP_EOL;
        file_put_contents(self::$logPath, $logMessage, FILE_APPEND);
    }

    public static function getLogContent($lines = 100) {
        if (!isset(self::$logPath)) {
            self::init();
        }

        if (!file_exists(self::$logPath)) {
            return "Nenhum log encontrado.";
        }

        $file = new SplFileObject(self::$logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $start = max(0, $totalLines - $lines);
        $logs = [];

        $file->seek($start);
        while (!$file->eof()) {
            $logs[] = $file->current();
            $file->next();
        }

        return implode('', $logs);
    }

    public static function clear() {
        if (!isset(self::$logPath)) {
            self::init();
        }

        if (file_exists(self::$logPath)) {
            file_put_contents(self::$logPath, '');
        }
    }
}