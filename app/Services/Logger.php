<?php

namespace FluentMailbox\Services;

class Logger
{
    private static $logFile = 'fluent-mailbox-debug.log';

    public static function log($message, $data = [])
    {
        $uploadDir = wp_upload_dir();
        $baseDir = $uploadDir['basedir'] . '/fluent-mailbox';
        
        if (!file_exists($baseDir)) {
            wp_mkdir_p($baseDir);
        }

        $file = $baseDir . '/' . self::$logFile;
        $timestamp = current_time('mysql');
        
        $logEntry = "[$timestamp] $message";
        if (!empty($data)) {
            $logEntry .= ' ' . print_r($data, true);
        }
        $logEntry .= PHP_EOL;

        file_put_contents($file, $logEntry, FILE_APPEND);
    }

    public static function getLog()
    {
        $uploadDir = wp_upload_dir();
        $file = $uploadDir['basedir'] . '/fluent-mailbox/' . self::$logFile;

        if (file_exists($file)) {
            // value to check last 50 lines
            return self::tailCustom($file, 100);
        }

        return "Log file not found at: $file";
    }

    public static function clean()
    {
        $uploadDir = wp_upload_dir();
        $file = $uploadDir['basedir'] . '/fluent-mailbox/' . self::$logFile;
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private static function tailCustom($filepath, $lines = 1) {
        $f = @fopen($filepath, "rb");
        if ($f === false) return false;

        $buffer = 4096;
        fseek($f, -1, SEEK_END);
        if (fread($f, 1) != "\n") $lines -= 1;
        
        $output = '';
        $chunk = '';

        while (ftell($f) > 0 && $lines >= 0) {
            $seek = min(ftell($f), $buffer);
            fseek($f, -$seek, SEEK_CUR);
            $chunk = fread($f, $seek);
            $output = $chunk . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");
        }

        while ($lines++ < 0) {
            $output = substr($output, strpos($output, "\n") + 1);
        }
        fclose($f);
        return $output;
    }
}
