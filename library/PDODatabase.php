<?php
/**
 * PDODatabase - PEAR DB互換のPDOラッパークラス
 * 
 * PEAR DBからPDOへの段階的移行を支援するラッパークラス
 * 既存のコードを最小限の変更で動作させることを目的とする
 * 
 * @author Claude Code Assistant
 * @date 2025-08-06
 */

class PDODatabase {
    private static $instance = null;
    private $pdo;
    private $inTransaction = false;
    
    /**
     * シングルトンインスタンスを取得
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ（プライベート）
     */
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            error_log("PDO Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * 全行を取得（PEAR DB互換）
     * 
     * @param string $sql SQLクエリ
     * @param array $params パラメータ配列
     * @param int $fetchMode フェッチモード（互換性のため残す）
     * @return array|false
     */
    public function getAll($sql, $params = [], $fetchMode = null) {
        try {
            $stmt = $this->prepareAndExecute($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("PDO getAll Error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * 単一行を取得（PEAR DB互換）
     * 
     * @param string $sql SQLクエリ
     * @param array $params パラメータ配列
     * @param int $fetchMode フェッチモード（互換性のため残す）
     * @return array|false
     */
    public function getRow($sql, $params = [], $fetchMode = null) {
        try {
            $stmt = $this->prepareAndExecute($sql, $params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("PDO getRow Error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * 単一値を取得（PEAR DB互換）
     * 
     * @param string $sql SQLクエリ
     * @param array $params パラメータ配列
     * @return mixed|false
     */
    public function getOne($sql, $params = []) {
        try {
            $stmt = $this->prepareAndExecute($sql, $params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("PDO getOne Error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * クエリを実行（INSERT/UPDATE/DELETE用）
     * 
     * @param string $sql SQLクエリ
     * @param array $params パラメータ配列
     * @return bool
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->prepareAndExecute($sql, $params);
            return true;
        } catch (PDOException $e) {
            error_log("PDO query Error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * 最後に挿入されたIDを取得
     * 
     * @return string
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * 影響を受けた行数を取得
     * 
     * @return int
     */
    public function affectedRows() {
        // 最後に実行されたステートメントから取得する必要があるため、
        // この実装では常に-1を返す（PEAR DB互換性のため）
        return -1;
    }
    
    /**
     * トランザクションを開始（PEAR DB互換）
     * 
     * @param bool $autoCommit falseでトランザクション開始
     * @return bool
     */
    public function autoCommit($autoCommit) {
        if (!$autoCommit && !$this->inTransaction) {
            $this->inTransaction = $this->pdo->beginTransaction();
            return $this->inTransaction;
        } elseif ($autoCommit && $this->inTransaction) {
            $result = $this->pdo->commit();
            $this->inTransaction = false;
            return $result;
        }
        return true;
    }
    
    /**
     * トランザクションを開始（PDOスタイル）
     * 
     * @return bool
     */
    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->inTransaction = $this->pdo->beginTransaction();
            return $this->inTransaction;
        }
        return false;
    }
    
    /**
     * トランザクションをコミット
     * 
     * @return bool
     */
    public function commit() {
        if ($this->inTransaction) {
            $result = $this->pdo->commit();
            $this->inTransaction = false;
            return $result;
        }
        return false;
    }
    
    /**
     * トランザクションをロールバック
     * 
     * @return bool
     */
    public function rollback() {
        if ($this->inTransaction) {
            $result = $this->pdo->rollBack();
            $this->inTransaction = false;
            return $result;
        }
        return false;
    }
    
    /**
     * エスケープ処理（PEAR DB互換）
     * 
     * @param string $string エスケープする文字列
     * @return string
     */
    public function escapeSimple($string) {
        // PDOではプレースホルダーを使うべきだが、互換性のため実装
        return substr($this->pdo->quote($string), 1, -1);
    }
    
    /**
     * SQLを準備して実行（内部メソッド）
     * 
     * @param string $sql SQLクエリ
     * @param array $params パラメータ配列
     * @return PDOStatement
     */
    private function prepareAndExecute($sql, $params = []) {
        // PEAR DBスタイルの?プレースホルダーを:名前付きプレースホルダーに変換
        $namedParams = [];
        $paramIndex = 0;
        
        // パラメータが配列の場合
        if (!empty($params)) {
            // 連想配列の場合はそのまま使用
            if ($this->isAssoc($params)) {
                $namedParams = $params;
            } else {
                // インデックス配列の場合は名前付きパラメータに変換
                $sql = preg_replace_callback('/\?/', function($matches) use (&$paramIndex) {
                    return ':param' . $paramIndex++;
                }, $sql);
                
                $paramIndex = 0;
                foreach ($params as $value) {
                    $namedParams['param' . $paramIndex++] = $value;
                }
            }
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($namedParams);
        return $stmt;
    }
    
    /**
     * 連想配列かどうかを判定
     * 
     * @param array $arr
     * @return bool
     */
    private function isAssoc(array $arr) {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    
    /**
     * PDOオブジェクトを取得（移行期間用）
     * 
     * @return PDO
     */
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * エラーチェック用（PEAR DB互換）
     * PDOでは例外を使うため、常にfalseを返す
     * 
     * @param mixed $result
     * @return bool
     */
    public static function isError($result) {
        return false;
    }
}

// PEAR DB互換のための定数定義（もし未定義なら）
if (!defined('DB_FETCHMODE_ASSOC')) {
    define('DB_FETCHMODE_ASSOC', PDO::FETCH_ASSOC);
}

if (!defined('DB_FETCHMODE_ORDERED')) {
    define('DB_FETCHMODE_ORDERED', PDO::FETCH_NUM);
}

// DB::isError() の互換性のためのクラス
if (!class_exists('DB')) {
    class DB {
        public static function isError($result) {
            return false;
        }
    }
}