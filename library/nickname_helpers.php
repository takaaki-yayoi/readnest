<?php
/**
 * ニックネームヘルパー関数
 * ニックネーム表示の問題を解決するための共通関数
 */

declare(strict_types=1);

/**
 * ニックネームが有効かどうかを検証
 * 
 * @param mixed $nickname 検証するニックネーム
 * @return bool 有効な場合はtrue
 */
function isValidNickname($nickname): bool {
    // NULL、空文字、'NULL'文字列、空白のみの場合は無効
    if ($nickname === null || $nickname === '' || $nickname === 'NULL' || $nickname === 'null') {
        return false;
    }
    
    // 文字列でない場合は無効
    if (!is_string($nickname)) {
        return false;
    }
    
    // トリムして空の場合は無効
    if (trim($nickname) === '') {
        return false;
    }
    
    // 「名無しさん」は無効（デフォルト値として扱わない）
    if ($nickname === '名無しさん') {
        return false;
    }
    
    // 「0」は有効なニックネームとして扱う
    // その他の文字列は有効
    return true;
}

/**
 * デフォルトのニックネームを生成
 * 
 * @param string $user_id ユーザーID
 * @return string デフォルトのニックネーム
 */
function generateDefaultNickname(string $user_id): string {
    // ユーザーIDの末尾4文字を使用してユニークな表示名を生成
    // 4文字未満の場合は全体を使用
    $suffix = strlen($user_id) > 4 ? substr($user_id, -4) : $user_id;
    return '読書家' . $suffix;
}

/**
 * ニックネームを安全に取得
 * データベースから取得したニックネームを検証し、無効な場合はデフォルト値を返す
 * 
 * @param array|null $user_info ユーザー情報配列
 * @param string|int $user_id ユーザーID（デフォルト生成用）
 * @return string 有効なニックネーム
 */
function getSafeNickname($user_info, $user_id): string {
    // user_idを文字列に変換
    $user_id = (string)$user_id;
    
    if (!$user_info || !is_array($user_info)) {
        return generateDefaultNickname($user_id);
    }
    
    $nickname = $user_info['nickname'] ?? null;
    
    if (isValidNickname($nickname)) {
        return $nickname;
    }
    
    return generateDefaultNickname($user_id);
}

/**
 * キャッシュされた活動データのニックネームを検証・修正
 * 
 * @param array $activities 活動データの配列
 * @return array 修正された活動データ
 */
function validateCachedNicknames(array $activities): array {
    global $g_db;
    
    if (empty($activities)) {
        return $activities;
    }
    
    $invalidUserIds = [];
    
    // 無効なニックネームを持つユーザーIDを収集
    foreach ($activities as $activity) {
        if (isset($activity['user_name']) && isset($activity['user_id'])) {
            if (!isValidNickname($activity['user_name'])) {
                $invalidUserIds[] = $activity['user_id'];
            }
        }
    }
    
    // 無効なニックネームがある場合、データベースから一括再取得（効率化）
    if (!empty($invalidUserIds)) {
        $invalidUserIds = array_unique($invalidUserIds); // 重複を除去
        $placeholders = implode(',', array_fill(0, count($invalidUserIds), '?'));
        $sql = "SELECT user_id, nickname FROM b_user WHERE user_id IN ($placeholders)";
        $result = $g_db->getAll($sql, $invalidUserIds, DB_FETCHMODE_ASSOC);
        
        $nicknames = [];
        if (!DB::isError($result)) {
            foreach ($result as $row) {
                $nickname = isValidNickname($row['nickname']) ? $row['nickname'] : generateDefaultNickname($row['user_id']);
                $nicknames[$row['user_id']] = $nickname;
            }
        }
        
        // 存在しないユーザーIDに対してもデフォルトニックネームを生成
        foreach ($invalidUserIds as $user_id) {
            if (!isset($nicknames[$user_id])) {
                $nicknames[$user_id] = generateDefaultNickname($user_id);
            }
        }
        
        // データを修正
        foreach ($activities as &$activity) {
            if (isset($activity['user_id']) && isset($nicknames[$activity['user_id']])) {
                $activity['user_name'] = $nicknames[$activity['user_id']];
            }
        }
        
        error_log("WARNING: Fixed " . count($invalidUserIds) . " invalid nicknames in cached activities.");
    }
    
    return $activities;
}

/**
 * キャッシュ前のニックネームを事前検証
 * キャッシュ保存前に呼び出して、無効なニックネームを修正
 * @param array $users ユーザー情報の配列
 * @return array 修正されたユーザー情報
 */
function preValidateNicknames($users) {
    global $g_db;
    
    if (empty($users)) {
        return $users;
    }
    
    // user_id => nickname のマップを作成
    $userNicknameMap = [];
    $invalidUserIds = [];
    
    foreach ($users as $user) {
        $user_id = isset($user['user_id']) ? $user['user_id'] : null;
        $nickname = isset($user['nickname']) ? $user['nickname'] : 
                    (isset($user['user_name']) ? $user['user_name'] : null);
        
        if ($user_id) {
            if (!isValidNickname($nickname)) {
                $invalidUserIds[] = $user_id;
            } else {
                $userNicknameMap[$user_id] = $nickname;
            }
        }
    }
    
    // 無効なニックネームのユーザーを一括再取得
    if (!empty($invalidUserIds)) {
        $invalidUserIds = array_unique($invalidUserIds);
        $placeholders = implode(',', array_fill(0, count($invalidUserIds), '?'));
        $sql = "SELECT user_id, nickname FROM b_user WHERE user_id IN ($placeholders)";
        $result = $g_db->getAll($sql, $invalidUserIds, DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($result)) {
            foreach ($result as $row) {
                $validNickname = isValidNickname($row['nickname']) 
                    ? $row['nickname'] 
                    : generateDefaultNickname($row['user_id']);
                $userNicknameMap[$row['user_id']] = $validNickname;
            }
        }
        
        // DBから取得できなかったユーザーにもデフォルトニックネームを生成
        foreach ($invalidUserIds as $user_id) {
            if (!isset($userNicknameMap[$user_id])) {
                $userNicknameMap[$user_id] = generateDefaultNickname($user_id);
            }
        }
    }
    
    // 元のデータを修正
    foreach ($users as &$user) {
        if (isset($user['user_id']) && isset($userNicknameMap[$user['user_id']])) {
            if (isset($user['nickname'])) {
                $user['nickname'] = $userNicknameMap[$user['user_id']];
            }
            if (isset($user['user_name'])) {
                $user['user_name'] = $userNicknameMap[$user['user_id']];
            }
        }
    }
    
    return $users;
}

/**
 * ニックネームのデバッグ情報を生成
 * 
 * @param string $user_id ユーザーID
 * @param mixed $nickname ニックネーム
 * @param string $context 文脈（例: 'cache', 'database', 'generated'）
 * @return void
 */
function debugNickname(string $user_id, $nickname, string $context = ''): void {
    $debug_info = [
        'user_id' => $user_id,
        'nickname' => $nickname,
        'nickname_type' => gettype($nickname),
        'is_valid' => isValidNickname($nickname),
        'context' => $context,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Debug info logged
}