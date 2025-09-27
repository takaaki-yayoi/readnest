<?php
/**
 * Logger Library for ReadNest
 * 
 * Provides unified logging functionality for all scripts
 */

declare(strict_types=1);

/**
 * Log a message to file and optionally to stdout
 * 
 * @param string $message The message to log
 * @param string $level Log level (INFO, WARNING, ERROR)
 * @param string $logFile Path to log file
 * @param bool $echoOutput Whether to echo to stdout
 */
function logMessage(string $message, string $level = 'INFO', string $logFile = '', bool $echoOutput = true): void {
    // Default log directory
    if (empty($logFile)) {
        $logDir = dirname(dirname(__FILE__)) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/cron_' . date('Y-m-d') . '.log';
    }
    
    // Format timestamp
    $timestamp = date('Y-m-d H:i:s');
    
    // Format log entry
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
    
    // Write to file
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Echo to stdout if requested (for cron email notifications)
    if ($echoOutput) {
        echo $logEntry;
    }
}

/**
 * Log an error message
 * 
 * @param string $message The error message
 * @param string $logFile Path to log file
 * @param bool $echoOutput Whether to echo to stdout
 */
function logError(string $message, string $logFile = '', bool $echoOutput = true): void {
    logMessage($message, 'ERROR', $logFile, $echoOutput);
}

/**
 * Log a warning message
 * 
 * @param string $message The warning message
 * @param string $logFile Path to log file
 * @param bool $echoOutput Whether to echo to stdout
 */
function logWarning(string $message, string $logFile = '', bool $echoOutput = true): void {
    logMessage($message, 'WARNING', $logFile, $echoOutput);
}

/**
 * Log an info message
 * 
 * @param string $message The info message
 * @param string $logFile Path to log file
 * @param bool $echoOutput Whether to echo to stdout
 */
function logInfo(string $message, string $logFile = '', bool $echoOutput = true): void {
    logMessage($message, 'INFO', $logFile, $echoOutput);
}

/**
 * Log a debug message (only if debug mode is enabled)
 * 
 * @param string $message The debug message
 * @param string $logFile Path to log file
 * @param bool $echoOutput Whether to echo to stdout
 */
function logDebug(string $message, string $logFile = '', bool $echoOutput = true): void {
    // Check if debug mode is enabled
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        logMessage($message, 'DEBUG', $logFile, $echoOutput);
    }
}

/**
 * Log script execution summary
 * 
 * @param string $scriptName Name of the script
 * @param float $startTime Script start time from microtime(true)
 * @param array $stats Execution statistics
 * @param string $logFile Path to log file
 * @param bool $echoOutput Whether to echo to stdout
 */
function logExecutionSummary(string $scriptName, float $startTime, array $stats = [], string $logFile = '', bool $echoOutput = true): void {
    $executionTime = microtime(true) - $startTime;
    $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
    
    $summary = sprintf(
        "%s completed. Execution time: %.2f seconds, Memory usage: %.2fMB",
        $scriptName,
        $executionTime,
        $memoryUsage
    );
    
    if (!empty($stats)) {
        $statsStr = [];
        foreach ($stats as $key => $value) {
            $statsStr[] = "{$key}: {$value}";
        }
        $summary .= ". Stats: " . implode(', ', $statsStr);
    }
    
    logInfo($summary, $logFile, $echoOutput);
}

/**
 * Rotate log files
 * 
 * @param string $logDir Log directory path
 * @param int $keepDays Number of days to keep logs
 */
function rotateLogs(string $logDir, int $keepDays = 30): void {
    if (!is_dir($logDir)) {
        return;
    }
    
    $cutoffTime = time() - ($keepDays * 24 * 60 * 60);
    $files = glob($logDir . '/cron_*.log');
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoffTime) {
            unlink($file);
        }
    }
}