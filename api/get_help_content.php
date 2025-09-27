<?php
/**
 * ヘルプコンテンツAPI
 * AIアシスタントが最新のヘルプ情報を参照できるようにする
 */

declare(strict_types=1);

require_once(__DIR__ . '/../config.php');

// リクエストメソッドチェック
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ヘルプコンテンツを構造化して返す
$help_content = [
    'main_features' => [
        'book_management' => [
            'title' => '本の管理',
            'features' => [
                '本の検索・追加（Google Books API対応）',
                '手動での本の追加',
                '読書ステータス管理（いつか買う、未読、読書中、読了、昔読んだ）',
                '読書進捗の記録（ページ数）',
                'レビュー・評価機能（5段階評価）',
                'タグ管理（複数タグ対応）'
            ]
        ],
        'ai_features' => [
            'title' => 'AI機能',
            'features' => [
                'AI書評アシスタント - 感想からプロの書評を生成',
                'AIタグ生成 - 本の内容に基づいた適切なタグを提案',
                'AI本の推薦 - 読書履歴から好みに合う本を推薦',
                'AI読書分析 - 読書傾向を分析してインサイトを提供'
            ]
        ],
        'reading_insights' => [
            'title' => '読書インサイト',
            'features' => [
                '4つのモード（概要、AI分類、マップ、ペース）で多面的分析',
                'OpenAI Embeddingによる本の自動クラスタリング',
                '著者別・タグ別の視覚的な読書傾向表示',
                '読書速度と完読率の詳細分析',
                '本棚との双方向ナビゲーション'
            ]
        ],
        'social_features' => [
            'title' => 'ソーシャル機能',
            'features' => [
                'X（Twitter）連携 - 読書記録の自動投稿',
                '他ユーザーのレビュー閲覧',
                'ランキング機能（月間・総合）',
                'プロフィール公開・非公開設定'
            ]
        ],
        'auth_features' => [
            'title' => '認証機能',
            'features' => [
                'Googleログイン対応',
                'メールアドレスでの登録・ログイン',
                'パスワードリセット機能',
                'アカウント連携機能'
            ]
        ]
    ],
    'how_to_use' => [
        'getting_started' => [
            'title' => 'はじめ方',
            'steps' => [
                'Googleアカウントまたはメールアドレスで登録',
                'プロフィール設定（写真、自己紹介、年間目標読書数）',
                '公開設定の確認',
                '最初の本を追加'
            ]
        ],
        'add_book' => [
            'title' => '本の追加方法',
            'methods' => [
                '検索で追加: タイトルや著者名で検索',
                '手動で追加: ISBN入力または情報を直接入力',
                'バーコード読み取り（対応デバイスのみ）'
            ]
        ],
        'manage_reading' => [
            'title' => '読書管理',
            'actions' => [
                'ステータス変更: 本の詳細ページから変更',
                '進捗記録: 現在のページ数を入力',
                'レビュー投稿: 読了後に感想を記録',
                'タグ付け: 本の分類やジャンルを設定'
            ]
        ]
    ],
    'tips' => [
        'ai_usage' => [
            'title' => 'AI機能の活用',
            'tips' => [
                '具体的な感想を書くとより良い書評が生成されます',
                'AIタグ生成後、「すべて追加」ボタンで一括追加可能',
                '読書履歴が増えるほどAI推薦の精度が向上します'
            ]
        ],
        'efficiency' => [
            'title' => '効率的な使い方',
            'tips' => [
                '読書インサイトで全体的な傾向を把握',
                '本棚で詳細な管理を行う',
                'タグを活用して本を整理',
                'X連携で読書記録を自動共有'
            ]
        ]
    ],
    'troubleshooting' => [
        'login_issues' => [
            'title' => 'ログインできない場合',
            'solutions' => [
                'パスワードを忘れた場合は「パスワードを忘れた方」から再設定',
                'Googleログインエラーはキャッシュクリアで解決',
                'ポップアップブロックを解除'
            ]
        ],
        'book_not_found' => [
            'title' => '本が見つからない場合',
            'solutions' => [
                '異なるキーワードで再検索',
                'ISBNで検索してみる',
                '手動で本を追加'
            ]
        ]
    ]
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($help_content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>