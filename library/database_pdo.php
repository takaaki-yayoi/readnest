<?php
/**
 * PDO wrapper functions to replace PEAR DB methods
 * This file provides compatibility layer for migrating from PEAR DB to PDO
 */

// Debug SQL logging
require_once(dirname(__FILE__) . '/debug_sql.php');

// Define PEAR DB constants if not already defined
if (!defined('DB_FETCHMODE_ASSOC')) {
    define('DB_FETCHMODE_ASSOC', 2);
}
if (!defined('DB_FETCHMODE_ORDERED')) {
    define('DB_FETCHMODE_ORDERED', 1);
}
if (!defined('DB_FETCHMODE_OBJECT')) {
    define('DB_FETCHMODE_OBJECT', 3);
}

// PDO wrapper class to provide PEAR DB-like methods
class DB_PDO {
    private $pdo;
    private $fetchMode = PDO::FETCH_ASSOC;
    private $lastStatement;
    private $inTransaction = false;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Execute a query and return a single value (like PEAR DB getOne)
     */
    public function getOne($sql, $params = array()) {
        try {
            
            // LIMIT/OFFSET を含むクエリの特別処理
            if (preg_match('/\sLIMIT\s+\?\s*(?:OFFSET\s+\?)?\s*$/i', $sql) && is_array($params) && !empty($params)) {
                // LIMIT と OFFSET の値を抽出
                $limit_offset_count = substr_count($sql, '?', strrpos($sql, 'LIMIT'));
                $limit_offset_params = array_splice($params, -$limit_offset_count);
                
                // LIMIT/OFFSET を直接SQLに埋め込む（整数値として検証）
                foreach ($limit_offset_params as $value) {
                    if (!is_numeric($value) || (int)$value < 0) {
                        throw new PDOException("Invalid LIMIT/OFFSET value");
                    }
                    $sql = preg_replace('/\?/', (int)$value, $sql, 1);
                }
            }
            
            $stmt = $this->pdo->prepare($sql);
            if ($params === null || (is_array($params) && empty($params))) {
                $stmt->execute();
            } else {
                $stmt->execute(is_array($params) ? $params : array($params));
            }
            $this->lastStatement = $stmt;
            $result = $stmt->fetchColumn();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Execute a query and return a single row (like PEAR DB getRow)
     */
    public function getRow($sql, $params = array(), $fetchMode = null) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $this->lastStatement = $stmt;
            
            $mode = $this->getFetchMode($fetchMode);
            $result = $stmt->fetch($mode);
            
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Execute a query and return all rows (like PEAR DB getAll)
     */
    public function getAll($sql, $params = array(), $fetchMode = null) {
        try {
            // パラメータの正規化
            if (!is_array($params)) {
                $params = $params === null ? array() : array($params);
            }
            
            // LIMIT/OFFSET を含むクエリの特別処理
            if (preg_match('/\sLIMIT\s+\?\s*(?:,\s*\?|\s+OFFSET\s+\?)?\s*$/i', $sql) && !empty($params)) {
                // SQLの最後のLIMIT句以降の?の数を数える
                $limitPos = stripos($sql, 'LIMIT');
                $sqlAfterLimit = substr($sql, $limitPos);
                $limit_offset_count = substr_count($sqlAfterLimit, '?');
                
                if ($limit_offset_count > 0 && count($params) >= $limit_offset_count) {
                    // LIMIT と OFFSET の値を抽出
                    $limit_offset_params = array_splice($params, -$limit_offset_count);
                    
                    // LIMIT/OFFSET を直接SQLに埋め込む（整数値として検証）
                    $sqlParts = explode('?', $sqlAfterLimit);
                    $newSqlAfterLimit = '';
                    foreach ($sqlParts as $i => $part) {
                        $newSqlAfterLimit .= $part;
                        if ($i < count($limit_offset_params)) {
                            $value = $limit_offset_params[$i];
                            if (!is_numeric($value) || (int)$value < 0) {
                                throw new PDOException("Invalid LIMIT/OFFSET value: " . $value);
                            }
                            $newSqlAfterLimit .= (int)$value;
                        }
                    }
                    $sql = substr($sql, 0, $limitPos) . $newSqlAfterLimit;
                }
            }
            
            $stmt = $this->pdo->prepare($sql);
            
            // パラメータの処理を改善
            if (empty($params)) {
                $stmt->execute();
            } else {
                $stmt->execute($params);
            }
            $this->lastStatement = $stmt;
            
            $mode = $this->getFetchMode($fetchMode);
            $result = $stmt->fetchAll($mode);
            
            // 結果が空の場合は空配列を返す
            return $result !== false ? $result : array();
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Execute a query (like PEAR DB query)
     */
    public function query($sql, $params = array()) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $this->lastStatement = $stmt; // 最後に実行されたstatementを保存
            return $stmt;
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Prepare a statement (like PEAR DB prepare)
     */
    public function prepare($sql) {
        try {
            return $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Execute multiple queries with the same prepared statement
     */
    public function executeMultiple($stmt, $data) {
        try {
            foreach ($data as $params) {
                $result = $stmt->execute($params);
                if (!$result) {
                    return new DB_Error("Execute failed");
                }
            }
            return true;
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Set autocommit mode
     */
    public function autoCommit($mode) {
        try {
            if ($mode) {
                // MySQLではデフォルトで自動コミットがON
                // トランザクションが開始されている場合はコミット
                if ($this->inTransaction) {
                    $this->pdo->commit();
                    $this->inTransaction = false;
                }
            } else {
                // 自動コミットをOFFにする = トランザクション開始
                if (!$this->inTransaction) {
                    $this->pdo->beginTransaction();
                    $this->inTransaction = true;
                }
            }
            return true;
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        try {
            $result = $this->pdo->beginTransaction();
            if ($result) {
                $this->inTransaction = true;
            }
            return $result;
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        try {
            $result = $this->pdo->commit();
            if ($result) {
                $this->inTransaction = false;
            }
            return $result;
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        try {
            $result = $this->pdo->rollBack();
            if ($result) {
                $this->inTransaction = false;
            }
            return $result;
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Get number of affected rows from last query
     */
    public function affectedRows() {
        try {
            // PDOでは最後に実行されたstatementを追跡する必要がある
            // そのためプロパティとして保存
            if (isset($this->lastStatement)) {
                return $this->lastStatement->rowCount();
            }
            return 0;
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Get the ID of the last inserted row
     */
    public function lastInsertId() {
        try {
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            return new DB_Error($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Quote a string for use in an SQL statement
     */
    public function quote($string) {
        try {
            return $this->pdo->quote($string);
        } catch (PDOException $e) {
            // フォールバック: 手動でエスケープ
            return "'" . addslashes($string) . "'";
        }
    }
    
    /**
     * Get the appropriate fetch mode
     */
    private function getFetchMode($fetchMode) {
        if ($fetchMode === null) {
            return $this->fetchMode;
        }
        
        // Convert PEAR DB fetch modes to PDO
        // DB_FETCHMODE_ASSOCは定数または値で渡される可能性がある
        if ($fetchMode === DB_FETCHMODE_ASSOC || $fetchMode === 2) {
            return PDO::FETCH_ASSOC;
        }
        
        // 数値の場合の変換
        switch ($fetchMode) {
            case 1: // DB_FETCHMODE_ORDERED
                return PDO::FETCH_NUM;
            case 2: // DB_FETCHMODE_ASSOC
                return PDO::FETCH_ASSOC;
            case 3: // DB_FETCHMODE_OBJECT
                return PDO::FETCH_OBJ;
            default:
                return PDO::FETCH_ASSOC; // Default to associative array
        }
    }
}

/**
 * Error class to mimic PEAR DB_Error
 */
class DB_Error {
    private $message;
    private $code;
    
    public function __construct($message, $code = null) {
        $this->message = $message;
        $this->code = $code;
    }
    
    public function getMessage() {
        return $this->message;
    }
    
    public function getCode() {
        return $this->code;
    }
}

/**
 * Static DB class to provide isError method
 */
class DB {
    public static function isError($value) {
        return ($value instanceof DB_Error);
    }
}


/**
 * Connect to database (wrapper function)
 */
function DB_Connect($pdo = null) {
    global $g_db;
    
    // If PDO is passed as parameter, use it
    if ($pdo instanceof PDO) {
        return new DB_PDO($pdo);
    }
    
    // If $g_db is already a PDO instance, wrap it
    if ($g_db instanceof PDO) {
        return new DB_PDO($g_db);
    }
    
    // Otherwise return the wrapped PDO
    return new DB_PDO($g_db);
}

/**
 * Helper function to get random string - defined in security.php
 */
// getRandomString()関数はsecurity.phpで定義されているため削除

/**
 * HTML escape function - defined in security.php
 */
// html()関数はsecurity.phpで定義されているため削除