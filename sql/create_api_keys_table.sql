-- API Key管理テーブル
CREATE TABLE IF NOT EXISTS b_api_keys (
    api_key_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL COMMENT 'API Keyの用途名（例: MCP Server）',
    is_active TINYINT(1) DEFAULT 1 COMMENT '有効フラグ',
    expires_at DATETIME NULL COMMENT '有効期限（NULLの場合は無期限）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL COMMENT '最終使用日時',
    INDEX idx_user_id (user_id),
    INDEX idx_api_key (api_key),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MCP API用のAPIキー管理';
