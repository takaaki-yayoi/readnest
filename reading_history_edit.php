<?php
/**
 * 読書履歴編集ページ
 * 進捗の修正・追加・削除が可能
 */

require_once('modern_config.php');
require_once(__DIR__ . '/library/csrf.php');

// ログインチェック
if (!checkLogin()) {
    header('Location: /');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];
$d_nickname = getNickname($mine_user_id);
$message = '';
$error = '';

// 本の情報を取得
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
if (!$book_id) {
    header('Location: /bookshelf.php');
    exit;
}

// 本の情報と権限チェック
$sql = "
    SELECT bl.*, br.title, br.author, br.image_url
    FROM b_book_list bl
    INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
    WHERE bl.book_id = ? AND bl.user_id = ?
";
$book = $g_db->getRow($sql, [$book_id, $mine_user_id], DB_FETCHMODE_ASSOC);

if (DB::isError($book) || !$book) {
    header('Location: /bookshelf.php');
    exit;
}

// Ajax処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // CSRF検証
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'CSRF token mismatch']);
        exit;
    }
    
    $action = $_POST['action'];
    $response = ['success' => false];
    
    switch ($action) {
        case 'add_progress':
            // 新規進捗追加
            $event_date = $_POST['event_date'] ?? date('Y-m-d');
            $event_time = $_POST['event_time'] ?? '12:00';
            $page_number = intval($_POST['page_number'] ?? 0);
            $memo = sanitizeInput($_POST['memo'] ?? '');
            
            if ($page_number > 0) {
                // update_dateは変更しない
                $sql = "
                    INSERT INTO b_book_event 
                    (user_id, book_id, memo, page, event_date, event)
                    VALUES (?, ?, ?, ?, ?, ?)
                ";
                
                $result = $g_db->query($sql, [
                    $mine_user_id,
                    $book_id,
                    $memo,
                    $page_number,
                    $event_date . ' ' . $event_time . ':00',
                    4  // イベントタイプ: 4 = 進捗更新
                ]);
                
                if (!DB::isError($result)) {
                    // 最新の読書記録の日時を取得
                    $latest_sql = "
                        SELECT MAX(event_date) as latest_date, 
                               (SELECT page FROM b_book_event 
                                WHERE user_id = ? AND book_id = ? 
                                ORDER BY event_date DESC LIMIT 1) as latest_page
                        FROM b_book_event 
                        WHERE user_id = ? AND book_id = ?
                    ";
                    $latest_info = $g_db->getRow($latest_sql, [$mine_user_id, $book_id, $mine_user_id, $book_id], DB_FETCHMODE_ASSOC);
                    
                    // ステータスを判定（最終ページに達した場合は読了）
                    $new_status = null;
                    if ($book['total_page'] > 0 && $latest_info['latest_page'] >= $book['total_page']) {
                        $new_status = READING_FINISH;  // 読了
                    } elseif ($latest_info['latest_page'] > 0) {
                        // 現在のステータスが「読了」または「昔読んだ」でない場合のみ「読書中」に更新
                        if (!in_array($book['status'], [READING_FINISH, READ_BEFORE])) {
                            $new_status = READING_NOW;  // 読書中
                        }
                    }
                    
                    // 本の情報を更新（current_page、update_date、必要に応じてstatus）
                    if ($new_status !== null) {
                        $update_sql = "
                            UPDATE b_book_list 
                            SET current_page = ?,
                                update_date = ?,
                                status = ?
                            WHERE book_id = ? AND user_id = ?
                        ";
                        $g_db->query($update_sql, [
                            $latest_info['latest_page'] ?: 0,
                            $latest_info['latest_date'],
                            $new_status,
                            $book_id, 
                            $mine_user_id
                        ]);
                    } else {
                        $update_sql = "
                            UPDATE b_book_list 
                            SET current_page = ?,
                                update_date = ?
                            WHERE book_id = ? AND user_id = ?
                        ";
                        $g_db->query($update_sql, [
                            $latest_info['latest_page'] ?: 0,
                            $latest_info['latest_date'],
                            $book_id, 
                            $mine_user_id
                        ]);
                    }
                    
                    $response['success'] = true;
                    $response['message'] = '進捗を追加しました';
                } else {
                    $response['error'] = '進捗の追加に失敗しました';
                }
            } else {
                $response['error'] = 'ページ数を入力してください';
            }
            break;
            
        case 'update_progress':
            // 既存進捗の更新
            $event_id = intval($_POST['event_id'] ?? 0);
            $event_date = $_POST['event_date'] ?? '';
            $event_time = $_POST['event_time'] ?? '12:00';
            $page_number = intval($_POST['page_number'] ?? 0);
            $memo = sanitizeInput($_POST['memo'] ?? '');
            
            if ($event_id && $page_number > 0) {
                // 権限チェック
                $check_sql = "SELECT * FROM b_book_event WHERE event_id = ? AND user_id = ?";
                $event = $g_db->getRow($check_sql, [$event_id, $mine_user_id]);
                
                if (!DB::isError($event) && $event) {
                    $sql = "
                        UPDATE b_book_event 
                        SET event_date = ?, page = ?, memo = ?
                        WHERE event_id = ? AND user_id = ?
                    ";
                    
                    $result = $g_db->query($sql, [
                        $event_date . ' ' . $event_time . ':00',
                        $page_number,
                        $memo,
                        $event_id,
                        $mine_user_id
                    ]);
                    
                    if (!DB::isError($result)) {
                        // 最新の読書記録情報を再計算
                        $recalc_sql = "
                            SELECT event_date, page 
                            FROM b_book_event 
                            WHERE user_id = ? AND book_id = ?
                            ORDER BY event_date DESC, event_id DESC
                            LIMIT 1
                        ";
                        $latest_event = $g_db->getRow($recalc_sql, [$mine_user_id, $book_id], DB_FETCHMODE_ASSOC);
                        
                        if ($latest_event) {
                            // ステータスを判定
                            $new_status = null;
                            if ($book['total_page'] > 0 && $latest_event['page'] >= $book['total_page']) {
                                $new_status = READING_FINISH;  // 読了
                            } elseif ($latest_event['page'] > 0) {
                                // 現在のステータスが「読了」または「昔読んだ」でない場合のみ「読書中」に更新
                                if (!in_array($book['status'], [READING_FINISH, READ_BEFORE])) {
                                    $new_status = READING_NOW;  // 読書中
                                }
                            }
                            
                            // 本の情報を更新
                            if ($new_status !== null) {
                                $update_sql = "
                                    UPDATE b_book_list 
                                    SET current_page = ?,
                                        update_date = ?,
                                        status = ?
                                    WHERE book_id = ? AND user_id = ?
                                ";
                                $g_db->query($update_sql, [
                                    $latest_event['page'],
                                    $latest_event['event_date'],
                                    $new_status,
                                    $book_id,
                                    $mine_user_id
                                ]);
                            } else {
                                $update_sql = "
                                    UPDATE b_book_list 
                                    SET current_page = ?,
                                        update_date = ?
                                    WHERE book_id = ? AND user_id = ?
                                ";
                                $g_db->query($update_sql, [
                                    $latest_event['page'],
                                    $latest_event['event_date'],
                                    $book_id,
                                    $mine_user_id
                                ]);
                            }
                        }
                        
                        $response['success'] = true;
                        $response['message'] = '進捗を更新しました';
                    } else {
                        $response['error'] = '進捗の更新に失敗しました';
                    }
                } else {
                    $response['error'] = '権限がありません';
                }
            } else {
                $response['error'] = '必要な情報が不足しています';
            }
            break;
            
        case 'delete_progress':
            // 進捗の削除
            $event_id = intval($_POST['event_id'] ?? 0);
            
            if ($event_id) {
                // 権限チェック
                $check_sql = "SELECT * FROM b_book_event WHERE event_id = ? AND user_id = ?";
                $event = $g_db->getRow($check_sql, [$event_id, $mine_user_id]);
                
                if (!DB::isError($event) && $event) {
                    $sql = "DELETE FROM b_book_event WHERE event_id = ? AND user_id = ?";
                    $result = $g_db->query($sql, [$event_id, $mine_user_id]);
                    
                    if (!DB::isError($result)) {
                        // 最新の読書記録情報を再計算（削除後）
                        $recalc_sql = "
                            SELECT event_date, page 
                            FROM b_book_event 
                            WHERE user_id = ? AND book_id = ?
                            ORDER BY event_date DESC, event_id DESC
                            LIMIT 1
                        ";
                        $latest_event = $g_db->getRow($recalc_sql, [$mine_user_id, $book_id], DB_FETCHMODE_ASSOC);
                        
                        if ($latest_event) {
                            // まだ読書記録がある場合は最新の情報で更新
                            // ステータスも再判定
                            $new_status = null;
                            if ($book['total_page'] > 0 && $latest_event['page'] >= $book['total_page']) {
                                $new_status = READING_FINISH;  // 読了
                            } elseif ($latest_event['page'] > 0) {
                                // 現在のステータスが「読了」または「昔読んだ」でない場合のみ「読書中」に更新
                                if (!in_array($book['status'], [READING_FINISH, READ_BEFORE])) {
                                    $new_status = READING_NOW;  // 読書中
                                }
                            }
                            
                            if ($new_status !== null) {
                                $update_sql = "
                                    UPDATE b_book_list 
                                    SET current_page = ?,
                                        update_date = ?,
                                        status = ?
                                    WHERE book_id = ? AND user_id = ?
                                ";
                                $g_db->query($update_sql, [
                                    $latest_event['page'],
                                    $latest_event['event_date'],
                                    $new_status,
                                    $book_id,
                                    $mine_user_id
                                ]);
                            } else {
                                $update_sql = "
                                    UPDATE b_book_list 
                                    SET current_page = ?,
                                        update_date = ?
                                    WHERE book_id = ? AND user_id = ?
                                ";
                                $g_db->query($update_sql, [
                                    $latest_event['page'],
                                    $latest_event['event_date'],
                                    $book_id,
                                    $mine_user_id
                                ]);
                            }
                        } else {
                            // 読書記録がすべて削除された場合
                            // ステータスが「読書中」の場合は「未読」に戻す
                            if ($book['status'] == READING_NOW) {
                                $update_sql = "
                                    UPDATE b_book_list 
                                    SET current_page = 0,
                                        status = ?
                                    WHERE book_id = ? AND user_id = ?
                                ";
                                $g_db->query($update_sql, [NOT_STARTED, $book_id, $mine_user_id]);
                            } else {
                                // 読了済みの本は読了のまま、ページ数だけリセット
                                $update_sql = "
                                    UPDATE b_book_list 
                                    SET current_page = 0
                                    WHERE book_id = ? AND user_id = ?
                                ";
                                $g_db->query($update_sql, [$book_id, $mine_user_id]);
                            }
                        }
                        
                        $response['success'] = true;
                        $response['message'] = '進捗を削除しました';
                    } else {
                        $response['error'] = '進捗の削除に失敗しました';
                    }
                } else {
                    $response['error'] = '権限がありません';
                }
            } else {
                $response['error'] = '削除する進捗が指定されていません';
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

// 読書履歴を取得（pageカラムを使用）
$sql = "
    SELECT event_id, user_id, book_id, event_date, page, memo, event, 
           page as number_of_pages,  -- 互換性のためエイリアス追加
           event_date as created_at  -- created_atがないのでevent_dateを使用
    FROM b_book_event
    WHERE user_id = ? AND book_id = ?
    ORDER BY event_date DESC, event_id DESC
";
$events = $g_db->getAll($sql, [$mine_user_id, $book_id], DB_FETCHMODE_ASSOC);

if (DB::isError($events)) {
    $events = [];
}

// ページ設定
$d_site_title = '読書履歴編集 - ' . $book['title'] . ' - ReadNest';
$g_meta_description = '読書履歴の編集・追加';
$g_meta_keyword = '読書履歴,編集,追加';

// テンプレートを使用
include(getTemplatePath('t_reading_history_edit.php'));
?>