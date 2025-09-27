<?php
/**
 * ユーザー登録プロセスのロギング機能
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

class RegistrationLogger {
    
    private static $logFile = null;
    private static $adminEmail = 'admin@readnest.jp';
    
    /**
     * ログファイルのパスを取得
     */
    private static function getLogFile(): string {
        if (self::$logFile === null) {
            $logDir = dirname(__DIR__) . '/logs/registration';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            self::$logFile = $logDir . '/' . date('Y-m') . '_registration.log';
        }
        return self::$logFile;
    }
    
    /**
     * ログエントリを記録
     */
    private static function writeLog(string $level, string $event, array $data = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];
        
        $logLine = "[{$timestamp}] [{$level}] {$event} | " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        
        // ファイルに記録
        file_put_contents(self::getLogFile(), $logLine, FILE_APPEND | LOCK_EX);
        
        // エラーログにも記録（デバッグ用）
        if ($level === 'ERROR' || $level === 'WARNING') {
            error_log("[REGISTRATION {$level}] {$event} | " . json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }
    
    /**
     * 管理者へのメール通知
     */
    private static function notifyAdmin(string $subject, string $message, array $data = []): void {
        try {
            mb_language("Japanese");
            mb_internal_encoding("UTF-8");
            
            $fullMessage = $message . "\n\n";
            $fullMessage .= "【詳細データ】\n";
            $fullMessage .= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
            $fullMessage .= "【環境情報】\n";
            $fullMessage .= "日時: " . date('Y-m-d H:i:s') . "\n";
            $fullMessage .= "IPアドレス: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
            $fullMessage .= "ユーザーエージェント: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n";
            $fullMessage .= "\n--------------------------\n";
            $fullMessage .= "ReadNest Registration Monitor\n";
            
            $headers = "From: ReadNest Monitor <noreply@readnest.jp>\r\n";
            $headers .= "Reply-To: noreply@readnest.jp\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            
            mb_send_mail(self::$adminEmail, "[ReadNest] " . $subject, $fullMessage, $headers);
            
        } catch (Exception $e) {
            self::writeLog('ERROR', 'Admin notification failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * 仮登録開始
     */
    public static function logRegistrationStart(string $email, string $nickname): void {
        self::writeLog('INFO', 'Registration started', [
            'email' => $email,
            'nickname' => $nickname
        ]);
    }
    
    /**
     * 仮登録成功
     */
    public static function logInterimRegistrationSuccess(string $email, string $nickname, string $interimId): void {
        self::writeLog('INFO', 'Interim registration successful', [
            'email' => $email,
            'nickname' => $nickname,
            'interim_id' => substr($interimId, 0, 8) . '...' // セキュリティのため一部のみ記録
        ]);
    }
    
    /**
     * 仮登録失敗
     */
    public static function logInterimRegistrationFailed(string $email, string $error): void {
        self::writeLog('ERROR', 'Interim registration failed', [
            'email' => $email,
            'error' => $error
        ]);
        
        // 管理者に通知
        self::notifyAdmin(
            '仮登録エラー発生',
            "仮登録処理でエラーが発生しました。\n\nメールアドレス: {$email}\nエラー: {$error}",
            ['email' => $email, 'error' => $error]
        );
    }
    
    /**
     * メール送信結果
     */
    public static function logMailSent(string $email, bool $success): void {
        $event = $success ? 'Mail sent successfully' : 'Mail sending failed';
        $level = $success ? 'INFO' : 'ERROR';
        
        self::writeLog($level, $event, ['email' => $email]);
        
        if (!$success) {
            // メール送信失敗時は管理者に通知
            self::notifyAdmin(
                'メール送信エラー',
                "仮登録メールの送信に失敗しました。\n\nメールアドレス: {$email}",
                ['email' => $email]
            );
        }
    }
    
    /**
     * アクティベーション開始
     */
    public static function logActivationAttempt(string $interimId): void {
        self::writeLog('INFO', 'Activation attempt', [
            'interim_id' => substr($interimId, 0, 8) . '...'
        ]);
    }
    
    /**
     * アクティベーション成功
     */
    public static function logActivationSuccess(int $userId, string $email): void {
        self::writeLog('INFO', 'Activation successful', [
            'user_id' => $userId,
            'email' => $email
        ]);
        
        // 管理者に通知（新規ユーザー登録完了）
        self::notifyAdmin(
            '新規ユーザー登録完了',
            "新しいユーザーが本登録を完了しました。\n\nユーザーID: {$userId}\nメールアドレス: {$email}",
            ['user_id' => $userId, 'email' => $email]
        );
    }
    
    /**
     * アクティベーション失敗
     */
    public static function logActivationFailed(string $interimId, string $reason): void {
        self::writeLog('WARNING', 'Activation failed', [
            'interim_id' => substr($interimId, 0, 8) . '...',
            'reason' => $reason
        ]);
    }
    
    /**
     * 重複登録試行
     */
    public static function logDuplicateAttempt(string $email, string $status): void {
        self::writeLog('WARNING', 'Duplicate registration attempt', [
            'email' => $email,
            'existing_status' => $status
        ]);
    }
    
    /**
     * 期限切れアクティベーション
     */
    public static function logExpiredActivation(string $interimId): void {
        self::writeLog('WARNING', 'Expired activation attempt', [
            'interim_id' => substr($interimId, 0, 8) . '...'
        ]);
    }
    
    /**
     * 統計サマリーを生成（管理画面用）
     */
    public static function getRegistrationStats(string $period = 'today'): array {
        $logFile = self::getLogFile();
        if (!file_exists($logFile)) {
            return [];
        }
        
        $stats = [
            'registrations_started' => 0,
            'interim_success' => 0,
            'activations_success' => 0,
            'mail_failures' => 0,
            'duplicate_attempts' => 0,
            'expired_attempts' => 0,
            'errors' => []
        ];
        
        $startTime = match($period) {
            'today' => strtotime('today'),
            'week' => strtotime('-7 days'),
            'month' => strtotime('-30 days'),
            default => strtotime('today')
        };
        
        $lines = file($logFile);
        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $timestamp = strtotime($matches[1]);
                if ($timestamp < $startTime) continue;
                
                if (strpos($line, 'Registration started') !== false) $stats['registrations_started']++;
                if (strpos($line, 'Interim registration successful') !== false) $stats['interim_success']++;
                if (strpos($line, 'Activation successful') !== false) $stats['activations_success']++;
                if (strpos($line, 'Mail sending failed') !== false) $stats['mail_failures']++;
                if (strpos($line, 'Duplicate registration attempt') !== false) $stats['duplicate_attempts']++;
                if (strpos($line, 'Expired activation attempt') !== false) $stats['expired_attempts']++;
                
                if (strpos($line, '[ERROR]') !== false) {
                    $stats['errors'][] = trim($line);
                }
            }
        }
        
        return $stats;
    }
}
?>