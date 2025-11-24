-- OAuth 2.0用のテーブル

-- OAuthクライアント管理
CREATE TABLE IF NOT EXISTS b_oauth_clients (
    client_id VARCHAR(64) PRIMARY KEY,
    user_id INT NOT NULL,
    client_secret VARCHAR(128) NOT NULL,
    client_name VARCHAR(100) NOT NULL COMMENT 'クライアント名（例: Claude.ai）',
    redirect_uris TEXT NOT NULL COMMENT 'リダイレクトURI（改行区切り）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OAuth認可コード
CREATE TABLE IF NOT EXISTS b_oauth_authorization_codes (
    code VARCHAR(128) PRIMARY KEY,
    client_id VARCHAR(64) NOT NULL,
    user_id INT NOT NULL,
    redirect_uri VARCHAR(500) NOT NULL,
    scope VARCHAR(500) NOT NULL,
    code_challenge VARCHAR(128) NULL COMMENT 'PKCE code_challenge',
    code_challenge_method VARCHAR(10) NULL COMMENT 'PKCE method (S256)',
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OAuthアクセストークン
CREATE TABLE IF NOT EXISTS b_oauth_access_tokens (
    access_token VARCHAR(128) PRIMARY KEY,
    client_id VARCHAR(64) NOT NULL,
    user_id INT NOT NULL,
    scope VARCHAR(500) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OAuthリフレッシュトークン
CREATE TABLE IF NOT EXISTS b_oauth_refresh_tokens (
    refresh_token VARCHAR(128) PRIMARY KEY,
    access_token VARCHAR(128) NOT NULL,
    client_id VARCHAR(64) NOT NULL,
    user_id INT NOT NULL,
    scope VARCHAR(500) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_access_token (access_token),
    INDEX idx_client_id (client_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
