<?php
/**
 * نظام تسجيل الأحداث - يكتب في logs/app.log
 */

class Logger {
    private static string $logDir  = '';
    private static string $logFile = '';

    private static function init(): void {
        if (self::$logFile !== '') return;
        self::$logDir  = dirname(__DIR__) . '/logs';
        self::$logFile = self::$logDir . '/app.log';
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    private static function write(string $level, string $message, array $context = []): void {
        self::init();
        $ts      = date('Y-m-d H:i:s');
        $ctx     = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '-';
        $uri     = $_SERVER['REQUEST_URI'] ?? '-';
        $line    = "[$ts] $level: $message | ip=$ip uri=$uri$ctx" . PHP_EOL;
        file_put_contents(self::$logFile, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $msg, array $ctx = []): void  { self::write('INFO',  $msg, $ctx); }
    public static function warn(string $msg, array $ctx = []): void  { self::write('WARN',  $msg, $ctx); }
    public static function error(string $msg, array $ctx = []): void { self::write('ERROR', $msg, $ctx); }

    public static function exception(Throwable $e): void {
        self::error($e->getMessage(), [
            'file' => $e->getFile() . ':' . $e->getLine(),
            'trace' => substr($e->getTraceAsString(), 0, 500),
        ]);
    }

    /** تنظيف السجل تلقائياً إذا تجاوز 5 ميغابايت */
    public static function rotate(): void {
        self::init();
        if (is_file(self::$logFile) && filesize(self::$logFile) > 5 * 1024 * 1024) {
            rename(self::$logFile, self::$logDir . '/app.' . date('Ymd-His') . '.log');
        }
    }
}
