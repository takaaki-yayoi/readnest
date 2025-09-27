<?php
/**
 * Google OAuth 2.0 ライブラリ
 */

class GoogleOAuth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $scopes;
    
    public function __construct() {
        // 設定ファイルが存在しない場合はエラー
        if (!defined('GOOGLE_CLIENT_ID') || !defined('GOOGLE_CLIENT_SECRET')) {
            throw new Exception('Google OAuth設定が見つかりません。config/google_oauth.phpを設定してください。');
        }
        
        $this->client_id = GOOGLE_CLIENT_ID;
        $this->client_secret = GOOGLE_CLIENT_SECRET;
        $this->redirect_uri = GOOGLE_REDIRECT_URI;
        $this->scopes = GOOGLE_SCOPES;
    }
    
    /**
     * 認証URLを生成
     */
    public function getAuthUrl($state = null) {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        if ($state) {
            $params['state'] = $state;
        }
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * 認証コードをアクセストークンに交換
     */
    public function getAccessToken($code) {
        $url = 'https://oauth2.googleapis.com/token';
        
        $data = [
            'code' => $code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('アクセストークンの取得に失敗しました: ' . $response);
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new Exception('アクセストークンエラー: ' . $result['error_description']);
        }
        
        return $result;
    }
    
    /**
     * リフレッシュトークンでアクセストークンを更新
     */
    public function refreshAccessToken($refresh_token) {
        $url = 'https://oauth2.googleapis.com/token';
        
        $data = [
            'refresh_token' => $refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'refresh_token'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('アクセストークンの更新に失敗しました: ' . $response);
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new Exception('リフレッシュトークンエラー: ' . $result['error_description']);
        }
        
        return $result;
    }
    
    /**
     * ユーザー情報を取得
     */
    public function getUserInfo($access_token) {
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('ユーザー情報の取得に失敗しました: ' . $response);
        }
        
        $userInfo = json_decode($response, true);
        
        if (!$userInfo || !isset($userInfo['id'])) {
            throw new Exception('ユーザー情報が不正です');
        }
        
        return $userInfo;
    }
    
    /**
     * Google認証情報をデータベースに保存
     */
    public function saveGoogleAuth($user_id, $google_id, $google_email, $google_name, $google_picture, $access_token, $refresh_token, $expires_in) {
        global $g_db;
        
        $token_expires_at = date('Y-m-d H:i:s', time() + $expires_in);
        
        // b_userテーブルのgoogle_idを更新
        $update_user_sql = "UPDATE b_user SET google_id = ? WHERE user_id = ?";
        $result = $g_db->query($update_user_sql, [$google_id, $user_id]);
        
        if (DB::isError($result)) {
            throw new Exception('b_userのgoogle_id更新に失敗しました: ' . $result->getMessage());
        }
        
        // b_google_authテーブルの既存レコードを確認
        // user_idを文字列として扱う
        $user_id_str = (string)$user_id;
        $check_sql = "SELECT auth_id FROM b_google_auth WHERE user_id = ? OR google_id = ?";
        $existing = $g_db->getOne($check_sql, [$user_id_str, $google_id]);
        
        if ($existing) {
            // 更新
            $update_sql = "UPDATE b_google_auth SET 
                google_email = ?,
                google_name = ?,
                google_picture = ?,
                access_token = ?,
                refresh_token = ?,
                token_expires_at = ?
                WHERE user_id = ? OR google_id = ?";
            
            $result = $g_db->query($update_sql, [
                $google_email,
                $google_name,
                $google_picture,
                $access_token,
                $refresh_token,
                $token_expires_at,
                $user_id_str,
                $google_id
            ]);
        } else {
            // 新規作成
            $insert_sql = "INSERT INTO b_google_auth 
                (user_id, google_id, google_email, google_name, google_picture, access_token, refresh_token, token_expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $result = $g_db->query($insert_sql, [
                $user_id_str,
                $google_id,
                $google_email,
                $google_name,
                $google_picture,
                $access_token,
                $refresh_token,
                $token_expires_at
            ]);
        }
        
        if (DB::isError($result)) {
            throw new Exception('b_google_authの保存に失敗しました: ' . $result->getMessage());
        }
        
        return true;
    }
    
    /**
     * Google IDからユーザーを検索（削除済みユーザーは除外）
     */
    public function findUserByGoogleId($google_id) {
        global $g_db;
        
        // b_google_authとb_userをJOINして検索（アクティブユーザーのみ）
        $sql = "SELECT u.*, ga.* 
                FROM b_user u
                INNER JOIN b_google_auth ga ON u.user_id = ga.user_id
                WHERE ga.google_id = ? 
                AND u.status = " . USER_STATUS_ACTIVE;
        
        $result = $g_db->getRow($sql, [$google_id], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result)) {
            // b_google_authにレコードがない場合、b_userのgoogle_idでも検索（アクティブユーザーのみ）
            $sql = "SELECT * FROM b_user WHERE google_id = ? AND status = " . USER_STATUS_ACTIVE;
            $result = $g_db->getRow($sql, [$google_id], DB_FETCHMODE_ASSOC);
            
            if (DB::isError($result)) {
                return null;
            }
        }
        
        return $result;
    }
    
    /**
     * EmailからGoogle未連携のユーザーを検索（削除済みユーザーは除外）
     */
    public function findUnlinkedUserByEmail($email) {
        global $g_db;
        
        $sql = "SELECT u.* 
                FROM b_user u
                LEFT JOIN b_google_auth ga ON u.user_id = ga.user_id
                WHERE u.email = ? 
                AND ga.auth_id IS NULL 
                AND (u.google_id IS NULL OR u.google_id = '')
                AND u.status = " . USER_STATUS_ACTIVE;
        
        $result = $g_db->getRow($sql, [$email], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result)) {
            return null;
        }
        
        return $result;
    }
}