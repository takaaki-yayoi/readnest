<?php
/**
 * ブックリンク一覧ページ
 * 本の中で紹介されていた本（ブックリンク）を、紹介元の本ごと・紹介された本ごとに一覧する
 */

declare(strict_types=1);

require_once('modern_config.php');
require_once(__DIR__ . '/library/book_links.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$mine_user_id = (int)$_SESSION['AUTH_USER'];
$d_nickname = getNickname($mine_user_id);

// 表示方法: source = 紹介元の本ごと / target = 紹介された本ごと
$view = ($_GET['view'] ?? 'source') === 'target' ? 'target' : 'source';
// 本棚に入っていない本だけ表示
$not_in_shelf = !empty($_GET['not_in_shelf']);

$all_links = getUserBookLinks($mine_user_id);

$stats = [
    'total_links' => count($all_links),
    'source_books' => count(array_unique(array_column($all_links, 'source_book_id'))),
    'not_in_shelf' => 0,
];
$not_in_shelf_asins = [];
foreach ($all_links as $link) {
    if (empty($link['target_book_id'])) {
        $not_in_shelf_asins[$link['target_asin']] = true;
    }
}
$stats['not_in_shelf'] = count($not_in_shelf_asins);

$links = $not_in_shelf
    ? array_values(array_filter($all_links, function ($link) { return empty($link['target_book_id']); }))
    : $all_links;

// グループ化（getUserBookLinks() は新しいリンク順なので、グループも最近追加した順に並ぶ）
$groups = [];
foreach ($links as $link) {
    $item = formatBookLinkForDisplay($link);
    if ($view === 'source') {
        $key = (int)$link['source_book_id'];
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'title' => (string)$link['source_title'],
                'author' => (string)$link['source_author'],
                'image_url' => !empty($link['source_image_url']) ? (string)$link['source_image_url'] : '/img/no-image-book.png',
                'url' => '/book/' . $key,
                'items' => [],
            ];
        }
        $groups[$key]['items'][] = $item;
    } else {
        $key = (string)$link['target_asin'];
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'title' => $item['title'],
                'author' => $item['author'],
                'image_url' => $item['image_url'],
                'url' => $item['url'],
                'in_shelf' => !empty($item['target_book_id']),
                'items' => [],
            ];
        }
        $groups[$key]['items'][] = [
            'title' => (string)$link['source_title'],
            'author' => (string)$link['source_author'],
            'image_url' => !empty($link['source_image_url']) ? (string)$link['source_image_url'] : '/img/no-image-book.png',
            'url' => '/book/' . (int)$link['source_book_id'],
            'page' => $item['page'],
            'memo' => $item['memo'],
        ];
    }
}

// 紹介元の本ごとの表示では、グループ内をページ番号順に並べる（本の中で出てきた順）
if ($view === 'source') {
    foreach ($groups as &$group) {
        usort($group['items'], function ($a, $b) {
            if ($a['page'] === $b['page']) {
                return 0;
            }
            if ($a['page'] === null) {
                return 1;
            }
            if ($b['page'] === null) {
                return -1;
            }
            return $a['page'] <=> $b['page'];
        });
    }
    unset($group);
}

// 表示方法・絞り込みの切り替えURL
function myBookLinksUrl(string $view, bool $not_in_shelf): string {
    $params = [];
    if ($view === 'target') {
        $params['view'] = 'target';
    }
    if ($not_in_shelf) {
        $params['not_in_shelf'] = 1;
    }
    return '/my_book_links.php' . ($params ? '?' . http_build_query($params) : '');
}

// ページメタ情報
$d_site_title = 'ブックリンク - ReadNest';
$g_meta_description = '本の中で紹介されていた本（ブックリンク）の一覧です。';
$g_meta_keyword = 'ブックリンク,紹介された本,読書記録,ReadNest';

include(getTemplatePath('t_my_book_links.php'));
