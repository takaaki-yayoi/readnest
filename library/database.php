<?php
// Include PDO wrapper functions
require_once(dirname(__FILE__) . '/database_pdo.php');

function authUser($username, $password) {
  global $g_db;
  
  $select_sql = 'select user_id from b_user where email=? and password=? and status=?';
  //$select_sql = 'select user_id from b_user where email=? and password=? and regist_date is not null';

  if(defined('DEBUG')) { 
    d($select_sql);
    d($username);
    d($password);
    d(sha1($password));
  }
  $result = $g_db->getOne($select_sql, array($username, sha1($password), USER_STATUS_ACTIVE));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


function authUserByMobileId($mobile_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  if($mobile_id == '') return NULL;
  
  $select_sql = 'select user_id from b_user where mobile_id=?';

  if(defined('DEBUG')) { 
    d($select_sql);
  }
  $result = $g_db->getOne($select_sql, array($mobile_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


function updateMobileId($user_id, $mobile_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $update_sql = 'UPDATE b_user SET mobile_id=? WHERE user_id=?';

  if(defined('DEBUG')) { 
    d($update_sql);
  }
  $result = $g_db->query($update_sql, array($mobile_id, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return DB_OPERATE_SUCCESS;
}

// get nickname
function getNickname($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select nickname from b_user where user_id=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
    return $user_id; // エラー時はuser_idを返す
  }
  
  // nicknameが空またはNULLの場合はuser_idを返す
  if (empty($result)) {
    return $user_id;
  }
  
  return $result;

}


// get recently updated book
function getRecentUpdatedBook($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select * from b_book_list where user_id=? and status=? order by update_date desc';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getRow($select_sql, array($user_id, READING_NOW), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}


// get recently finished book
function getRecentFinishedBook($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  //$select_sql = 'SELECT bl.* FROM b_book_list bl, b_book_event be WHERE bl.user_id=? AND bl.status=? AND bl.book_id=be.book_id ORDER BY be.event_date DESC';
  $select_sql = 'SELECT * FROM b_book_list WHERE user_id=? AND status=? ORDER BY update_date DESC';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getRow($select_sql, array($user_id, READING_FINISH), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}



// increment reference number
function incrementRefNum($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $update_sql = 'update b_book_list set number_of_refer=number_of_refer+1 where book_id=?';

  if(defined('DEBUG')) { d($update_sql); }
  logSQL($update_sql, array($book_id), __FUNCTION__);
  $result = $g_db->query($update_sql, array($book_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return DB_OPERATE_SUCCESS;
}


// setting auto login
function setAutoLoginKey($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $secret = 'SH;alncasd;k8792a';
  
  $sha1 = sha1($secret . mt_rand() . microtime());
  
  $update_sql = 'update b_user set autologin=? where user_id=?';
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array($sha1, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }

  return $sha1;
}


// get user by autologinkey
function getUserByAutologiKey($autologin_key) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select user_id from b_user where autologin=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($autologin_key));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// setting app token
function setAppToken($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $secret = 'SH;aglssg;j2323b';
  
  $sha1 = sha1($user_id . $secret . mt_rand() . microtime());
  
  $update_sql = 'update b_user set iphone_app_token=? where user_id=?';
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array($sha1, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }

  return $sha1;
}


// get user by app token
function getUserByAppToken($autologin_key) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select user_id from b_user where iphone_app_token=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($autologin_key));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// user registration interimly
function registUserInterim($email, $nickname, $password) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $secret = 'SH;alncasd;k8792a';
  $sha1 = sha1($secret . mt_rand() . microtime());

  // DATETIME型のcreate_dateにはNOW()を使用、statusは仮登録(0)
  $create_sql = 'insert into b_user(email, create_date, nickname, password, interim_id, diary_policy, status) values(?, NOW(), ?, ?, ?, ?, ?)';
  if(defined('DEBUG')) {
    d($create_sql); 
    d($password);
    d(sha1($password));
  }

  $result = $g_db->query($create_sql, array($email, $nickname, sha1($password), $sha1, 1, USER_STATUS_INTERIM));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
    return false;
  }

  return $sha1;
}


// get user by interim_id
function getUserByInterimId($interim_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // 1時間以内に作成された仮登録ユーザーのみ取得
  $select_sql = 'select user_id from b_user where interim_id=? and create_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($interim_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// user activation
function userActivate($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // DATETIME型で保存、statusを本登録(1)に更新
  $update_sql = 'update b_user set regist_date=NOW(), status=? where user_id=?';
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array(USER_STATUS_ACTIVE, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  

  return DB_OPERATE_SUCCESS;
}


// get user by email and nickname
function getUserForReissue($email, $nickname) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select user_id from b_user where email=? and nickname=? and regist_date is not null';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($email, $nickname));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// reissue password
function passwordReissue($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $tmp_password = getRandomString(6);

  $update_sql = 'update b_user set password=? where user_id=?';
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array(sha1($tmp_password), $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }

  return $tmp_password;

}


// get user information
function getUserInformation($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select * from b_user where user_id=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getRow($select_sql, array($user_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// update user information
function updateUserInformation($user_id, $email, $nickname, $password, $amazon_id, $diary_policy, $books_per_year, $pager_type, $introduction) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $result = $g_db->autoCommit(false);
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }

  if($email != '') {
    $update_sql = 'update b_user set email=? where user_id=?';
    if(defined('DEBUG')) { d($update_sql); }
    $result = $g_db->query($update_sql, array($email, $user_id));
    if(DB::isError($result)) {
      $g_db->rollback();
      trigger_error($result->getMessage() . '<br />');
    }
  }

  if($nickname != '') {
    $update_sql = 'update b_user set nickname=? where user_id=?';
    if(defined('DEBUG')) { d($update_sql); }
    $result = $g_db->query($update_sql, array($nickname, $user_id));
    if(DB::isError($result)) {
      $g_db->rollback();
      trigger_error($result->getMessage() . '<br />');
    }
  }
  
  if($password != '') {
    $update_sql = 'update b_user set password=? where user_id=?';
    if(defined('DEBUG')) { d($update_sql); }
    $result = $g_db->query($update_sql, array(sha1($password), $user_id));
    if(DB::isError($result)) {
      $g_db->rollback();
      trigger_error($result->getMessage() . '<br />');
    }
  }

  $update_sql = 'update b_user set books_per_year=? where user_id=?';
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array($books_per_year, $user_id));
  if(DB::isError($result)) {
    $g_db->rollback();
    trigger_error($result->getMessage() . '<br />');
  }

  $update_sql = 'update b_user set associate_id=? where user_id=?';
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array($amazon_id, $user_id));
  if(DB::isError($result)) {
    $g_db->rollback();
    trigger_error($result->getMessage() . '<br />');
  }

  if($diary_policy != '') {
    $update_sql = 'update b_user set diary_policy=? where user_id=?';
    if(defined('DEBUG')) { d($update_sql); }
    $result = $g_db->query($update_sql, array($diary_policy, $user_id));
    if(DB::isError($result)) {
      $g_db->rollback();
      trigger_error($result->getMessage() . '<br />');
    }
  }

  $update_sql = 'update b_user set pager_type=? where user_id=?';
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array($pager_type, $user_id));
  if(DB::isError($result)) {
    $g_db->rollback();
    trigger_error($result->getMessage() . '<br />');
  }

  $update_sql = 'update b_user set introduction=? where user_id=?';
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array($introduction, $user_id));
  if(DB::isError($result)) {
    $g_db->rollback();
    trigger_error($result->getMessage() . '<br />');
  }

  $g_db->commit();
  
  $result = $g_db->autoCommit(true);
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }

  return DB_OPERATE_SUCCESS;
}


// delete user information
function deleteUserInformation($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // @TODO delete other data
  
  if($user_id != '' && is_numeric($user_id)) {

    $result = $g_db->autoCommit(false);
    if(DB::isError($result)) {
      trigger_error($result->getMessage() . '<br />');
    }

    $sql = 'delete from b_user where user_id=?';
    if(defined('DEBUG')) { d($sql); }
    $result = $g_db->query($sql, array($user_id));
    if(DB::isError($result)) {
      $g_db->rollback();
      trigger_error($result->getMessage() . '<br />');
    }

    $sql = 'delete from b_book_list where user_id=?';
    if(defined('DEBUG')) { d($sql); }
    $result = $g_db->query($sql, array($user_id));
    if(DB::isError($result)) {
      $g_db->rollback();
      trigger_error($result->getMessage() . '<br />');
    }

    $sql = 'delete from b_book_event where user_id=?';
    if(defined('DEBUG')) { d($sql); }
    $result = $g_db->query($sql, array($user_id));
    if(DB::isError($result)) {
      $g_db->rollback();
      trigger_error($result->getMessage() . '<br />');
    }

    $g_db->commit();
  
    $result = $g_db->autoCommit(true);
    if(DB::isError($result)) {
      trigger_error($result->getMessage() . '<br />');
    }

  }

  return DB_OPERATE_SUCCESS;
}




// create book
function createBook($user_id, $book_name, $book_asin, $book_isbn, $author, $memo, $total_page, $status, $detail_url, $image_url, $finished_date = null, $categories = null) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // 読了日の設定（読了ステータスの場合）
  // create_dateはINT型なのでUNIX_TIMESTAMP()を使用
  if (($status == READING_FINISH || $status == READ_BEFORE) && !empty($finished_date)) {
    $create_sql = 'insert into b_book_list(name, amazon_id, isbn, author, memo, total_page, user_id, create_date, update_date, status, detail_url, image_url, finished_date) values(?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), NOW(), ?, ?, ?, ?)';
    $params = array($book_name, $book_asin, $book_isbn, $author, $memo, $total_page, $user_id, $status, $detail_url, $image_url, $finished_date);
  } else {
    $create_sql = 'insert into b_book_list(name, amazon_id, isbn, author, memo, total_page, user_id, create_date, update_date, status, detail_url, image_url) values(?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), NOW(), ?, ?, ?)';
    $params = array($book_name, $book_asin, $book_isbn, $author, $memo, $total_page, $user_id, $status, $detail_url, $image_url);
  }
  
  if(defined('DEBUG')) { d($create_sql); }
  $result = $g_db->query($create_sql, $params);
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  // For PDO, use lastInsertId() instead of LAST_INSERT_ID()
  try {
    $book_id = $g_db->lastInsertId();
  } catch (PDOException $e) {
    trigger_error($e->getMessage() . '<br />');
    return null;
  }
  
  // 読了ステータスの本を追加した場合は統計を更新
  if ($status == READING_FINISH || $status == READ_BEFORE) {
    updateUserReadingStat($user_id);
  }
  
  // 本棚キャッシュをクリア（新しい本追加時の即座な反映のため）
  if (file_exists(dirname(__FILE__) . '/cache.php')) {
    require_once(dirname(__FILE__) . '/cache.php');
    $cache = getCache();
    
    // 本棚関連のキャッシュをクリア
    $cache->delete('bookshelf_stats_' . md5((string)$user_id));
    $cache->delete('user_tags_' . md5((string)$user_id));
    
    // 本一覧キャッシュをクリア（新しい本が追加されるため）
    $statuses = ['', '1', '2', '3', '4']; // 全体、読む前、読んでる、読了、読む予定
    // 新しいソート形式に対応
    $sorts = [
      'update_date_desc', 'update_date_asc',
      'finished_date_desc', 'finished_date_asc',
      'rating_desc', 'rating_asc',
      'title_asc', 'title_desc',
      'author_asc', 'author_desc',
      'pages_desc', 'pages_asc',
      'created_date_desc', 'created_date_asc',
      // レガシー互換用
      'update_date', 'create_date', 'name', 'author', 'rating', 'status', 'total_page', 'current_page'
    ];
    
    foreach ($statuses as $cache_status) {
      foreach ($sorts as $sort) {
        $booksCacheKey = 'bookshelf_books_' . md5((string)$user_id . '_' . $cache_status . '_' . $sort . '_' . '' . '_' . '' . '_' . '' . '_' . '' . '_' . '' . '_' . '');
        $cache->delete($booksCacheKey);
      }
    }
  }
  
  // ジャンル情報を保存（genre_detector.phpが読み込まれている場合のみ）
  if ($book_id && function_exists('determineBookGenre')) {
    $genres = determineBookGenre($book_id, $categories, $book_name, $author);
    if (!empty($genres)) {
      saveBookGenres($book_id, $genres);
    }
  }
  
  // ユーザー作家クラウドを更新（リアルタイム更新）
  if ($book_id && file_exists(dirname(__FILE__) . '/user_author_cloud.php')) {
    require_once(dirname(__FILE__) . '/user_author_cloud.php');
    UserAuthorCloud::triggerUpdate($user_id, $book_id);
  }
  
  // b_book_repositoryに本の情報を追加（まだ存在しない場合）
  if ($book_id && !empty($book_asin)) {
    // ASINで既存レコードをチェック
    $check_sql = 'SELECT asin FROM b_book_repository WHERE asin = ?';
    $exists = $g_db->getOne($check_sql, array($book_asin));
    
    if (!$exists) {
      // 存在しない場合は追加
      addBookToRepository($book_asin, $book_name, $image_url, $author);
    }
  }

  return $book_id;
}



// bought book
function boughtBook($user_id, $book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // Use NOW() instead of time() to avoid 2038 problem
  // DATETIME型で更新
  $update_sql = 'update b_book_list set status=?, update_date=NOW() where book_id=? and user_id=?';
  
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array(NOT_STARTED, $book_id, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  return DB_OPERATE_SUCCESS;
}


// get bookshelf information
function getBookshelf($user_id, $status, $order='update_date_desc') {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // SQLインジェクション対策: ホワイトリスト方式（新しいソート形式に対応）
  $allowed_orders = array(
    // レガシー互換性
    'update_date' => 'update_date DESC',
    'name' => 'name ASC',
    'author' => 'author ASC',
    'rating' => 'rating DESC',
    'status' => 'status ASC',
    'total_page' => 'total_page DESC',
    'current_page' => 'current_page DESC',
    // 新しいソート形式
    'update_date_desc' => 'update_date DESC',
    'update_date_asc' => 'update_date ASC',
    'finished_date_desc' => 'finished_date DESC',
    'finished_date_asc' => 'finished_date ASC',
    'rating_desc' => 'rating DESC, update_date DESC',
    'rating_asc' => 'rating ASC, update_date DESC',
    'title_asc' => 'name ASC',
    'title_desc' => 'name DESC',
    'author_asc' => 'author ASC',
    'author_desc' => 'author DESC',
    'pages_desc' => 'total_page DESC',
    'pages_asc' => 'total_page ASC',
    'created_date_desc' => 'create_date DESC',
    'created_date_asc' => 'create_date ASC'
  );
  
  // デフォルト値または許可された値のみ使用
  $order_clause = isset($allowed_orders[$order]) ? $allowed_orders[$order] : 'update_date DESC';
  
  if($status != '') {
    // 読了の場合は「昔読んだ」も含める
    if($status == READING_FINISH) {
      $status_where_clause = 'and status IN (?, ?)';
      $status_params = array(READING_FINISH, READ_BEFORE);
    } else {
      $status_where_clause = 'and status=?';
      $status_params = array($status);
    }
  } else {
    $status_where_clause = '';
    $status_params = array();
  }
  
  // b_book_repositoryテーブルから著者情報も取得
  $select_sql = "SELECT bl.*, COALESCE(br.author, bl.author, '') as author 
                 FROM b_book_list bl 
                 LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin 
                 WHERE bl.user_id = ? $status_where_clause 
                 ORDER BY $order_clause";

  if(defined('DEBUG')) { d($select_sql); }

  if($status != '') {
    $params = array_merge(array($user_id), $status_params);
    $result = $g_db->getAll($select_sql, $params, DB_FETCHMODE_ASSOC);
  } else {
    $result = $g_db->getAll($select_sql, array($user_id), DB_FETCHMODE_ASSOC);
  }

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}



// get bookshelf read_finished and read_before
function getReadBook($user_id, $sort_key) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $status_where_clause = 'and (status=' . READING_FINISH . ' OR status=' . READ_BEFORE . ')';
  
  if($sort_key == SORT_RATING) {
    $select_sql = "select * from b_book_list where user_id=? $status_where_clause order by rating desc, update_date desc";
  } else if($sort_key == SORT_NAME) {
    $select_sql = "select * from b_book_list where user_id=? $status_where_clause order by name asc";
  } else {
    $select_sql = "select * from b_book_list where user_id=? $status_where_clause order by update_date desc";
  }

  if(defined('DEBUG')) { d($select_sql); }

  $result = $g_db->getAll($select_sql, array($user_id), DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// search bookshelf read_finished and read_before
function searchReadBook($user_id, $keyword) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $status_where_clause = 'and (status=' . READING_FINISH . ' OR status=' . READ_BEFORE . ')';
  
  $select_sql = "select * from b_book_list where user_id=? $status_where_clause and name like ? order by name asc";
  if(defined('DEBUG')) { d($select_sql); }

  if($keyword != '') {
    $result = $g_db->getAll($select_sql, array($user_id, $keyword), DB_FETCHMODE_ASSOC);

    if(DB::isError($result)) {
      trigger_error($result->getMessage());
    }
  } else {
    $result = NULL;
  }
  
  return $result;
}


// search bookshelf
function searchBookshelf($user_id, $keyword) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $keyword = "%$keyword%";
  
  $select_sql = "select * from b_book_list where user_id=? and name like ? order by update_date desc";
  if(defined('DEBUG')) { d($select_sql); }

  if($keyword != '') {
    $result = $g_db->getAll($select_sql, array($user_id, $keyword), DB_FETCHMODE_ASSOC);

    if(DB::isError($result)) {
      trigger_error($result->getMessage());
    }
    
    /*
    if(mb_detect_encoding($result['name']) == 'SJIS-win') {
   
      $result['name'] = mb_convert_encoding($result['name'],  "UTF-8");
      $result['author'] = mb_convert_encoding($result['author'],  "UTF-8");
    }*/
    
  } else {
    $result = NULL;
  }
  
  return $result;
}

// get book information
function getBookInformation($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // b_book_repositoryテーブルから著者情報も取得
  $select_sql = 'SELECT bl.*, COALESCE(br.author, bl.author, "") as author 
                 FROM b_book_list bl 
                 LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin 
                 WHERE bl.book_id = ?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getRow($select_sql, array($book_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  if($result && mb_detect_encoding($result['name']) == 'SJIS-win') {
  
     $result['name'] = mb_convert_encoding($result['name'],  "UTF-8");
     $result['author'] = mb_convert_encoding($result['author'],  "UTF-8");
  }
  
  return $result;
}


function getBookInformationForIphone($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select name,status,book_id,detail_url,update_date,image_url from b_book_list where book_id=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getRow($select_sql, array($book_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// get bookshelf statistics
function getBookshelfStat($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select count(*) from b_book_list where user_id=? and (status=? or status=?)';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($user_id, READING_FINISH, READ_BEFORE));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  $read_book_num = $result;

  $select_sql = 'select sum(total_page) from b_book_list where user_id=? and (status=? or status=?)';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($user_id, READING_FINISH, READ_BEFORE));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  $read_page_num = $result;

  return array($read_book_num, $read_page_num);
}


// get bookshelf number
function getBookshelfNum($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $flag_array = array(BUY_SOMEDAY, NOT_STARTED, READING_NOW, READING_FINISH, READ_BEFORE);
  $return_array = array();
  
  $select_sql = 'select count(*) from b_book_list where user_id=? and status=?';

  for($i = 0; $i < count($flag_array); $i++) {
    if(defined('DEBUG')) { d($select_sql); }
    $result = $g_db->getOne($select_sql, array($user_id, $flag_array[$i]));
    if(DB::isError($result)) {
      trigger_error($result->getMessage());
    }
    
    $return_array[$flag_array[$i]] = $result;
  }
  
  return $return_array;
}


// get bookshelf statistics
function getUnreadBooknum($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select count(*) from b_book_list where user_id=? and (status=? or status=?)';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($user_id, READING_NOW, NOT_STARTED));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  $unread_book_num = $result;

  return $unread_book_num;
}



// get read finished book on specified day
function getFinishedBooks($user_id, $time_stamp) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $end_time = $time_stamp + (60 * 60 * 24);
  
  $select_sql = 'select * from b_book_event where user_id=? and (event_date between ? and ?) and event=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($user_id, $time_stamp, $end_time, READING_FINISH), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// get read finished book during this month
function getFinishedBooksInThisMonth($user_id, $date_key = '') {
  global $g_db;
  // $g_db is already a DB_PDO instance

  if($date_key == '') {
    $date = date('Y n d');

    $date = explode(' ', $date);
  } else {
    $date = explode('_', $date_key);

    if (!checkdate($date[MONTH], $date[DAY], $date[YEAR])) {
      $date = date('Y n d');
      $date = explode(' ', $date);
    }
  }

  //明示的に変数を整数型へ変換
  $date[MONTH] = (int) $date[MONTH];
  $date[YEAR] = (int) $date[YEAR];
  $date[DAY] = (int) $date[DAY];

  //今月の日数、最初の日、最後の日の曜日を得る
  $days = date('d', mktime(0, 0, 0, $date[MONTH]+1, 0, $date[YEAR]));

  $start_time = mktime(0, 0, 0, $date[MONTH], 1, $date[YEAR]);
  $end_time = mktime(23, 59, 59, $date[MONTH], $days, $date[YEAR]);
  
  $select_sql = 'select * from b_book_event where user_id=? and (event_date between ? and ?) and event=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($user_id, $start_time, $end_time, READING_FINISH), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}



// is bookmarked?
function is_bookmarked($user_id, $book_asin) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select book_id from b_book_list where user_id=? and amazon_id=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($user_id, $book_asin));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  if($result != NULL)
    return $result;
  else
    return false;
}


// is bookmarked and finished?
// not bookmarked : false
// bookmarked but not finished : not finised book id
// bookmarked and finished : array
function is_bookmarked_finished($user_id, $book_asin) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select book_id,status from b_book_list where user_id=? and amazon_id=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($user_id, $book_asin), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  if($result != NULL) {
    // check if all the existing books are finished
    for($i = 0; $i < count($result); $i++) {
      $book_id = $result[$i]['book_id'];
      $status_id = $result[$i]['status'];
      
      // existing book is not finised
      if($status_id != READING_FINISH && $status_id != READ_BEFORE) {
        return $book_id;
      }
    }
    return $result;
  } else
    return false;
}


function getFinishedNumber($user_id, $book_asin) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  if($book_asin=='') return 0;
  
  $select_sql = 'select book_id,status from b_book_list where user_id=? and amazon_id=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($user_id, $book_asin), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  $count = 0;
  
  if($result != NULL) {
    // check if all the existing books are finished
    for($i = 0; $i < count($result); $i++) {
      $book_id = $result[$i]['book_id'];
      $status_id = $result[$i]['status'];
      
      // existing book is not finised
      if($status_id == READING_FINISH || $status_id == READ_BEFORE) {
        $count++;
      }
    }
    return $count;
  } else
    return 0;
}


// update book information
function updateBook($user_id, $book_id, $status, $rating, $comment, $finished_date = null) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // まず現在の本の情報を取得
  $book_info = $g_db->getRow('SELECT total_page, current_page, status as old_status FROM b_book_list WHERE user_id=? AND book_id=?', array($user_id, $book_id));
  if(DB::isError($book_info)) {
    trigger_error($book_info->getMessage());
    return DB_OPERATE_FAILED;
  }
  
  // memo_updatedはINT型なのでUNIX_TIMESTAMP()を使用
  // update_dateはDATETIME型なのでNOW()を使用
  $sql = 'update b_book_list set status=?, rating=?, memo=?, memo_updated=UNIX_TIMESTAMP(), update_date=NOW()';
  $params = array($status, $rating, $comment);
  
  // 読了・既読状態に変更する場合は、current_pageをtotal_pageに更新
  if (($status == READING_FINISH || $status == READ_BEFORE) && $book_info) {
    $sql .= ', current_page=?';
    $params[] = $book_info['total_page'];
  }
  
  // 読了日の更新（読了・既読状態の場合のみ）
  if (($status == READING_FINISH || $status == READ_BEFORE) && $finished_date !== null) {
    $sql .= ', finished_date=?';
    $params[] = $finished_date;
  }
  
  $sql .= ' where user_id=? and book_id=?';
  $params[] = $user_id;
  $params[] = $book_id;

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, $params);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  // 読了状態に変更した場合、読書イベントを作成
  // セッションから抑制フラグを確認
  $suppress_event = isset($_SESSION['suppress_book_event']) && $_SESSION['suppress_book_event'] === true;
  if ($suppress_event) {
    // フラグをクリア
    unset($_SESSION['suppress_book_event']);
  }
  
  if (!$suppress_event && ($status == READING_FINISH || $status == READ_BEFORE) && $book_info) {
    // 読了に変更された場合（既に読了でない場合）
    if ($book_info['old_status'] != READING_FINISH && $book_info['old_status'] != READ_BEFORE) {
      // 読書イベントを作成（全ページ読了）
      if ($book_info['total_page'] > 0) {
        createEvent((int)$user_id, (int)$book_id, '読了', (int)$book_info['total_page']);
      }
    }
    // すでに読了だが、current_pageがtotal_pageでない場合も読了イベントを作成
    else if ($book_info['current_page'] < $book_info['total_page'] && $book_info['total_page'] > 0) {
      createEvent($user_id, $book_id, '読了', $book_info['total_page']);
    }
  }
  
  // Post to X if a review with rating was added
  if ($rating > 0 && !empty($comment)) {
    require_once dirname(__FILE__) . '/x_api.php';
    postReviewToX($user_id, $book_id, $rating, $comment);
  }
  
  // Update user reading stats if book was marked as finished
  if ($status == READING_FINISH || $status == READ_BEFORE) {
    updateUserReadingStat($user_id);
  }
  
  // 本棚キャッシュをクリア（ステータス変更時の即座な反映のため）
  if ($book_info && $book_info['old_status'] != $status) {
    if (file_exists(dirname(__FILE__) . '/cache.php')) {
      require_once(dirname(__FILE__) . '/cache.php');
      $cache = getCache();
      
      // 本棚統計キャッシュをクリア
      $cache->delete('bookshelf_stats_' . md5((string)$user_id));
      
      // ステータス変更があった場合のみ本一覧キャッシュをクリア
      $statuses = ['', '1', '2', '3', '4']; // 全体、読む前、読んでる、読了、読む予定
      $sorts = ['update_date', 'create_date', 'name']; // 並び順
      
      foreach ($statuses as $cache_status) {
        foreach ($sorts as $sort) {
          $booksCacheKey = 'bookshelf_books_' . md5((string)$user_id . '_' . $cache_status . '_' . $sort . '_' . '' . '_' . '' . '_' . '' . '_' . '' . '_' . '' . '_' . '');
          $cache->delete($booksCacheKey);
        }
      }
    }
  }
  
  // ユーザー作家クラウドを更新（リアルタイム更新）
  if (file_exists(dirname(__FILE__) . '/user_author_cloud.php')) {
    require_once(dirname(__FILE__) . '/user_author_cloud.php');
    UserAuthorCloud::triggerUpdate($user_id, $book_id);
  }

  return DB_OPERATE_SUCCESS;
}


// update book page total
function updateBookPageTotal($book_id, $page_total) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // Use NOW() instead of time() to avoid 2038 problem
  
  // update book status
  $sql = 'update b_book_list set total_page=? where book_id=?';

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($page_total, $book_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return DB_OPERATE_SUCCESS;
}

// delete book
function deleteBook($user_id, $book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $result = $g_db->autoCommit(false);
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }

  $sql = 'delete from b_book_list where user_id=? and book_id=?';
  
  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($user_id, $book_id));
  if(DB::isError($result)) {
    $g_db->rollback();
    trigger_error($result->getMessage() . '<br />');
  }

  $sql = 'delete from b_book_event where user_id=? and book_id=?';
  
  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($user_id, $book_id));
  if(DB::isError($result)) {
    $g_db->rollback();
    trigger_error($result->getMessage() . '<br />');
  }

  $g_db->commit();
  
  $result = $g_db->autoCommit(true);
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  // 削除後に読書統計を更新
  updateUserReadingStat($user_id);
  
  // 本棚キャッシュをクリア（削除後の即座な反映のため）
  if (file_exists(dirname(__FILE__) . '/cache.php')) {
    require_once(dirname(__FILE__) . '/cache.php');
    $cache = getCache();
    
    // 本棚関連のキャッシュをクリア
    $cache->delete('bookshelf_stats_' . md5((string)$user_id));
    $cache->delete('user_tags_' . md5((string)$user_id));
    
    // すべての本棚表示キャッシュをクリアする
    // ユーザーIDを含むすべての組み合わせのキャッシュを削除
    clearUserBookshelfCache($user_id);
  }
  
  // ユーザー作家クラウドを更新（リアルタイム更新）
  if (file_exists(dirname(__FILE__) . '/user_author_cloud.php')) {
    require_once(dirname(__FILE__) . '/user_author_cloud.php');
    UserAuthorCloud::triggerUpdate($user_id, $book_id);
  }

  return DB_OPERATE_SUCCESS;
}

/**
 * ユーザーの本棚関連のすべてのキャッシュをクリア
 * 
 * @param int $user_id ユーザーID
 * @return void
 */
function clearUserBookshelfCache($user_id) {
  if (file_exists(dirname(__FILE__) . '/cache.php')) {
    require_once(dirname(__FILE__) . '/cache.php');
    $cache = getCache();
    
    // キャッシュディレクトリのパスを取得
    $cache_dir = dirname(__DIR__) . '/cache';
    
    if (is_dir($cache_dir)) {
      // ユーザーIDを含む可能性のあるすべてのキャッシュキーパターンを生成
      // 本棚の表示で使用される可能性のあるすべてのパラメータの組み合わせ
      
      // ステータスの全パターン
      $statuses = ['', '0', '1', '2', '3', '4', '5'];
      
      // ソート順の全パターン
      $sorts = [
        '', 'update_date', 'create_date', 'name', 'author', 'rating',
        'update_date_desc', 'update_date_asc',
        'create_date_desc', 'create_date_asc',
        'title_asc', 'title_desc',
        'author_asc', 'author_desc',
        'rating_desc', 'rating_asc',
        'finished_date_desc', 'finished_date_asc'
      ];
      
      // 各種フィルタパターンを含むキャッシュキーを削除
      foreach ($statuses as $status) {
        foreach ($sorts as $sort) {
          // 基本パターン（フィルタなし）
          $base_key = 'bookshelf_books_' . md5((string)$user_id . '_' . $status . '_' . $sort . '______');
          $cache->delete($base_key);
          
          // タグフィルタ付きパターン
          $tag_filters = ['', 'no_tags'];
          for ($tag_id = 1; $tag_id <= 100; $tag_id++) {
            $tag_filters[] = (string)$tag_id;
          }
          
          foreach ($tag_filters as $tag_filter) {
            // カバーフィルタ付きパターン
            $cover_filters = ['', 'no_cover', 'has_cover'];
            foreach ($cover_filters as $cover_filter) {
              // 検索タイプ付きパターン
              $search_types = ['', 'title', 'author', 'isbn', 'all'];
              foreach ($search_types as $search_type) {
                // 年月フィルタは動的なので一般的なパターンのみ
                $years = ['', date('Y'), date('Y', strtotime('-1 year'))];
                $months = [''];
                for ($m = 1; $m <= 12; $m++) {
                  $months[] = sprintf('%02d', $m);
                }
                
                foreach ($years as $year) {
                  foreach ($months as $month) {
                    // 検索ワードは動的なので空文字のみ
                    $cache_key = 'bookshelf_books_' . md5(
                      (string)$user_id . '_' . 
                      $status . '_' . 
                      $sort . '_' . 
                      $search_type . '_' . 
                      '' . '_' .  // search_word
                      $year . '_' . 
                      $month . '_' . 
                      $tag_filter . '_' . 
                      $cover_filter
                    );
                    $cache->delete($cache_key);
                  }
                }
              }
            }
          }
        }
      }
      
      // 一般的な検索ワードパターンも削除（部分一致は困難なので主要パターンのみ）
      // これにより大部分のキャッシュがクリアされる
    }
  }
}

// create event
function createEvent($user_id, $book_id, $memo, $number_of_pages, $event_date = null, $suppress_x_post = false) {
  // 引数の型を整数に変換
  $user_id = (int)$user_id;
  $book_id = (int)$book_id;
  $number_of_pages = (int)$number_of_pages;
  global $g_db;
  // $g_db is already a DB_PDO instance

  $result = $g_db->autoCommit(false);
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }

  // get book page num
  $sql = 'select total_page, status, current_page from b_book_list where book_id=?';
  
  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->getRow($sql, array($book_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    $g_db->rollback();
    trigger_error($result->getMessage());
  }
  
  $current_page = (int)($result['current_page'] ?? 0);
  $book_page_num = (int)($result['total_page'] ?? 0);
  $book_status = (int)($result['status'] ?? 0);

  // check if the same page submission
  //if($number_of_pages == $current_page) return;

  // 総ページ数が0の場合はエラーを防ぐ
  if ($book_page_num == 0) {
    $g_db->rollback();
    $g_db->autoCommit(true);
    return DB_OPERATE_FAILED;
  }

  if($number_of_pages > $book_page_num) {
    $number_of_pages = $book_page_num;
  }
  
  if($number_of_pages == $book_page_num) {
    $status = READING_FINISH;
  } else {
    $status = READING_NOW;
  }
  
  // check book reading done
  if($book_status == READ_BEFORE) {
    $status = READ_BEFORE;
  }
  if($book_status == READING_FINISH) {
    $status = READING_FINISH;
  }

  // DATETIME型で挿入（マイグレーション後はこちらを使用）
  if ($event_date !== null) {
    // 指定された日付を使用（読了日設定時など）
    $sql = 'insert into b_book_event(user_id, book_id, event_date, page, memo, event) values(?, ?, ?, ?, ?, ?)';
  } else {
    // 現在時刻を使用（通常の進捗更新）
    $sql = 'insert into b_book_event(user_id, book_id, event_date, page, memo, event) values(?, ?, NOW(), ?, ?, ?)';
  }

  if(defined('DEBUG')) { d($sql); }
  if ($event_date !== null) {
    $result = $g_db->query($sql, array($user_id, $book_id, $event_date, $number_of_pages, $memo, $status));
  } else {
    $result = $g_db->query($sql, array($user_id, $book_id, $number_of_pages, $memo, $status));
  }
  if(DB::isError($result)) {
    $g_db->rollback();
    trigger_error($result->getMessage() . '<br />');
  }
  
  // check book reading done
  if($book_status == READING_FINISH || $book_status == READ_BEFORE) {
    $status = READING_FINISH;
    $number_of_pages = $book_page_num;
  }

  // Use NOW() to avoid 2038 problem
  // DATETIME型で更新
  // 読書進捗の更新は常にupdate_dateを更新する（CLAUDE.mdの仕様に従う）
  // 進捗更新は「読書状態の更新」として扱う
  $sql = 'update b_book_list set status=?, update_date=NOW(), current_page=? where book_id=?';

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($status, $number_of_pages, $book_id));
  if(DB::isError($result)) {
    $g_db->rollback();
    trigger_error($result->getMessage());
  }

  $g_db->commit();
  
  $result = $g_db->autoCommit(true);
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  // 読了イベントの場合は読書統計を更新
  if ($status == READING_FINISH) {
    updateUserReadingStat($user_id);
  }
  
  // 本棚キャッシュをクリア（読書進捗更新時の即座な反映のため）
  if (file_exists(dirname(__FILE__) . '/cache.php')) {
    require_once(dirname(__FILE__) . '/cache.php');
    $cache = getCache();
    
    // 本棚統計キャッシュをクリア（読了時のみ）
    if ($status == READING_FINISH) {
      $cache->delete('bookshelf_stats_' . md5((string)$user_id));
    }
    
    // 本一覧キャッシュをクリア（update_dateが変更されるため）
    $statuses = ['', '1', '2', '3', '4']; // 全体、読む前、読んでる、読了、読む予定
    // update_date関連のソートをクリア（進捗更新で並び順が変わるため）
    $sorts = ['update_date_desc', 'update_date_asc', 'update_date']; // 新旧両方の形式
    
    foreach ($statuses as $cache_status) {
      foreach ($sorts as $sort) {
        $booksCacheKey = 'bookshelf_books_' . md5((string)$user_id . '_' . $cache_status . '_' . $sort . '_' . '' . '_' . '' . '_' . '' . '_' . '' . '_' . '' . '_' . '');
        $cache->delete($booksCacheKey);
      }
    }
  }
  
  // Post to X if applicable (unless suppressed)
  if (!$suppress_x_post) {
    require_once dirname(__FILE__) . '/x_api.php';
  }
  
  // Check previous status to determine if this is a new reading start
  if (!$suppress_x_post) {
    if ($book_status == NOT_STARTED && $status == READING_NOW) {
      // User just started reading
      postStartReadingToX($user_id, $book_id);
    } elseif ($status == READING_FINISH) {
      // User just finished reading
      postFinishReadingToX($user_id, $book_id);
    } elseif ($book_status == READING_NOW && $status == READING_NOW && $number_of_pages > 0) {
    // User updated reading progress (not starting or finishing)
    // Post any progress update (threshold temporarily disabled)
    $previous_page = (int)$current_page;
    $page_diff = $number_of_pages - $previous_page;
    
    // Threshold is currently disabled - post all progress updates
    // To re-enable: uncomment the threshold checks below
    /*
    // Fixed thresholds: 10% or 50 pages
    $threshold_percentage = 0.1; // 10%
    $threshold_pages = 50;
    
    if ($book_page_num > 0) {
      $progress_percentage_diff = ($page_diff / $book_page_num);
      
      if ($page_diff >= $threshold_pages || $progress_percentage_diff >= $threshold_percentage) {
        postReadingProgressToX($user_id, $book_id, $number_of_pages, $book_page_num, $memo);
      }
    } elseif ($page_diff >= $threshold_pages) {
      // If we don't know total pages, just check page difference
      postReadingProgressToX($user_id, $book_id, $number_of_pages, 0, $memo);
    }
    */
    
      // Post all progress updates (no threshold)
      if ($page_diff > 0) {
        postReadingProgressToX($user_id, $book_id, $number_of_pages, $book_page_num, $memo);
      }
    }
  }

  return DB_OPERATE_SUCCESS;
}


// return event
function getEvent($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'SELECT * FROM b_book_event WHERE book_id=? ORDER BY event_date ASC';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($book_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// return event from user viewpoint
function getDiary($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select * from b_book_event where user_id=? order by event_date desc';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($user_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// get disclosed diary
function getDisclosedDiary($event = '') {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  if($event == '') {
    $event_part = '';
  } else {
    $event_part = ' and be.event=? ';
  }
  
  $limit_number = ' limit 1000';
  
  //$select_sql = "select be.* from b_book_event be, b_user bu where be.user_id=bu.user_id and bu.diary_policy=? $event_part order by be.event_date desc";
  $select_sql = "select be.book_id,be.event_date,be.event,be.memo,be.page,be.user_id from b_book_event be, b_user bu where be.user_id=bu.user_id and bu.diary_policy=? $event_part order by be.event_date desc $limit_number";

  if(defined('DEBUG')) { d($select_sql); }
  if($event == '') {
    $select_sql = "select be.book_id,be.event_date,be.event,be.memo,be.page,be.user_id from b_book_event be, b_user bu where be.user_id=bu.user_id and bu.diary_policy=1 and ( be.event=2 or be.event=3 ) order by be.event_date desc $limit_number";

    $result = $g_db->getAll($select_sql, NULL, DB_FETCHMODE_ASSOC);
  } else {
    $result = $g_db->getAll($select_sql, array('1', $event), DB_FETCHMODE_ASSOC);
  }
  
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// get book with asin
function getBooksWithAsin($asin) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  if($asin == '') return '';
  
  $select_sql = 'select * from b_book_list where amazon_id=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($asin), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// get book group by asin
function getBooksGroup() {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'SELECT name, amazon_id, COUNT(amazon_id) AS book_count, detail_url, image_url FROM b_book_list GROUP BY amazon_id HAVING book_count > 1 ORDER BY book_count DESC';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, NULL, DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}

// add book link
function addBookLink($user_id, $linked_from, $linked_to) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $memo = '';

  // create event
  $sql = 'insert into b_book_relation(user_id, from_book, to_book, memo, create_date) values (?, ?, ?, ?, NOW())';
  
  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($user_id, $linked_from, $linked_to, $memo));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }

  return DB_OPERATE_SUCCESS;
}


// add book link
function removeBookLink($user_id, $relation_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // create event
  $sql = 'delete from b_book_relation where relation_id=? and user_id=?';
  
  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($relation_id, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }

  return DB_OPERATE_SUCCESS;
}


// get books that this book links to.
function getBookLinkFrom($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select * from b_book_relation where from_book=? order by create_date desc';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($book_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}

// get book that links to me
function getBookLinkTo($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select * from b_book_relation where to_book=? order by create_date desc';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($book_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}

// is linked?
function isLinkedTo($book_from, $book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select * from b_book_relation where to_book=? and from_book=? order by create_date desc';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($book_id, $book_from), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}


// プロフィール写真のURLを取得
function getProfilePhotoURL($user_id, $mode = 'thumbnail') {
    $user_info = getUserInformation($user_id);
    if ($user_info && $user_info['photo'] && $user_info['photo_state'] == PHOTO_REGISTER_STATE) {
        return "https://readnest.jp/display_profile_photo.php?user_id={$user_id}&mode={$mode}";
    }
    return '/img/no-image-user.png';
}

// ユーザーIDとパスワードを確認
function checkPasswordById($user_id, $password) {
    global $g_db;
    
    // パスワードをSHA1でハッシュ化
    $password_hash = sha1($password);
    
    // ユーザーIDとパスワードが一致するか確認（regist_dateがnullでないことで有効なユーザーを判定）
    $check_sql = 'SELECT COUNT(*) FROM b_user WHERE user_id = ? AND password = ? AND regist_date IS NOT NULL';
    $result = $g_db->getOne($check_sql, array($user_id, $password_hash));
    
    if (DB::isError($result)) {
        trigger_error($result->getMessage());
        return false;
    }
    
    return ($result > 0);
}

// ユーザーアカウントを削除
function deleteUserAccount($user_id) {
    global $g_db;
    
    try {
        // トランザクション開始
        $g_db->autoCommit(false);
        
        // 関連データを削除（削除順序は外部キー制約を考慮）
        // 1. コメントを削除（テーブルが存在する場合）
        $delete_comments = 'DELETE FROM b_comment WHERE user_id = ? OR to_user = ?';
        $result = $g_db->query($delete_comments, array($user_id, $user_id));
        if (DB::isError($result)) {
            // テーブルが存在しない場合はスキップ
            if (strpos($result->getMessage(), "doesn't exist") !== false || strpos($result->getMessage(), "Table") !== false) {
                error_log('Warning: b_comment table not found, skipping...');
            } else {
                throw new Exception('Failed to delete comments: ' . $result->getMessage());
            }
        }
        
        // 2. 本のイベントを削除
        $delete_events = 'DELETE FROM b_book_event WHERE user_id = ?';
        $result = $g_db->query($delete_events, array($user_id));
        if (DB::isError($result)) {
            throw new Exception('Failed to delete book events: ' . $result->getMessage());
        }
        
        // 3. タグを削除
        $delete_tags = 'DELETE FROM b_book_tags WHERE user_id = ?';
        $result = $g_db->query($delete_tags, array($user_id));
        if (DB::isError($result)) {
            throw new Exception('Failed to delete tags: ' . $result->getMessage());
        }
        
        // 4. 自動ログインキーを削除
        $delete_autologin = 'DELETE FROM b_autologin WHERE user_id = ?';
        $result = $g_db->query($delete_autologin, array($user_id));
        if (DB::isError($result)) {
            // テーブルが存在しない場合はスキップ
            error_log('Warning: Failed to delete autologin keys: ' . $result->getMessage());
        }
        
        // 5. 本棚の本を削除
        $delete_books = 'DELETE FROM b_book_list WHERE user_id = ?';
        $result = $g_db->query($delete_books, array($user_id));
        if (DB::isError($result)) {
            throw new Exception('Failed to delete books: ' . $result->getMessage());
        }
        
        // 6. 最後にユーザーを削除（regist_dateをNULLにして論理削除、statusを削除済み(3)に）
        $delete_user = 'UPDATE b_user SET regist_date = NULL, email = CONCAT(email, "_deleted_", UNIX_TIMESTAMP()), password = "", nickname = CONCAT("削除済みユーザー_", UNIX_TIMESTAMP()), status = ? WHERE user_id = ?';
        $result = $g_db->query($delete_user, array(USER_STATUS_DELETED, $user_id));
        if (DB::isError($result)) {
            throw new Exception('Failed to delete user: ' . $result->getMessage());
        }
        
        // コミット
        $g_db->commit();
        $g_db->autoCommit(true);
        
        return true;
        
    } catch (Exception $e) {
        // ロールバック
        $g_db->rollback();
        $g_db->autoCommit(true);
        error_log('deleteUserAccount error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * データ移行：Unix timestampをMySQLのDATETIME形式に変換
 * 既存のupdate_dateがUnix timestampの場合にMySQLのDATETIME形式に変換する
 */
function migrateUnixTimestampsToDatetime() {
    global $g_db;
    
    try {
        // Unix timestampは通常10桁の数字なので、数値であり1000000000以上のものを対象とする
        $sql = "UPDATE b_book_list 
                SET update_date = FROM_UNIXTIME(update_date)
                WHERE update_date REGEXP '^[0-9]+$' 
                AND CAST(update_date AS UNSIGNED) > 1000000000 
                AND CAST(update_date AS UNSIGNED) < 2147483647";
        
        $result = $g_db->query($sql);
        if (DB::isError($result)) {
            error_log("Error migrating timestamps: " . $result->getMessage());
            return false;
        }
        
        // memo_updatedも同様に変換
        $sql2 = "UPDATE b_book_list 
                 SET memo_updated = FROM_UNIXTIME(memo_updated)
                 WHERE memo_updated REGEXP '^[0-9]+$' 
                 AND CAST(memo_updated AS UNSIGNED) > 1000000000 
                 AND CAST(memo_updated AS UNSIGNED) < 2147483647";
        
        $result2 = $g_db->query($sql2);
        if (DB::isError($result2)) {
            error_log("Error migrating memo_updated timestamps: " . $result2->getMessage());
            return false;
        }
        
        error_log("Successfully migrated Unix timestamps to DATETIME format");
        return true;
        
    } catch (Exception $e) {
        error_log("Exception during timestamp migration: " . $e->getMessage());
        return false;
    }
}

// add profile photo
function addProfilePhoto($user_id, $file_path, $validated_file_type) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $data = file_get_contents($file_path);

  $sql = 'update b_user set photo=?, photo_state=?, photo_mime=? where user_id=?';

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($data, PHOTO_CONFIRM_STATE, $validated_file_type, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  return DB_OPERATE_SUCCESS;
}


// add profile photo
function removeProfilePhoto($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $sql = 'update b_user set photo_state=? where user_id=?';

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array(PHOTO_DELETE_STATE, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  return DB_OPERATE_SUCCESS;
}


// add profile photo
function registerProfilePhoto($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $sql = 'update b_user set photo_state=? where user_id=?';

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array(PHOTO_REGISTER_STATE, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  return DB_OPERATE_SUCCESS;
}

function getConfirmPhoto($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  $select_sql = 'select * from b_user where user_id=? and photo_state=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getRow($select_sql, array($user_id, PHOTO_CONFIRM_STATE), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}


// search book memo
function searchReview($keyword) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = "select * from b_book_list bl, b_user bu where bl.user_id=bu.user_id and bu.diary_policy=? and (bl.memo like ? or bl.name like ?) order by update_date desc";

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array('1', $keyword, $keyword), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}


// add tag
function updateTag($user_id, $book_id, $tag_array) {
  global $g_db;
  
  // 既存のタグを削除
  $delete_sql = "DELETE FROM b_book_tags WHERE book_id = ? AND user_id = ?";
  $result = $g_db->query($delete_sql, array($book_id, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
    return DB_OPERATE_FAILED;
  }
  
  // 新しいタグを追加
  if (!empty($tag_array)) {
    $insert_sql = "INSERT INTO b_book_tags (book_id, user_id, tag_name, created_at) VALUES (?, ?, ?, NOW())";
    foreach ($tag_array as $tag) {
      $tag = trim($tag);
      if (!empty($tag)) {
        $result = $g_db->query($insert_sql, array($book_id, $user_id, $tag));
        if(DB::isError($result)) {
          trigger_error($result->getMessage());
        }
      }
    }
  }
  
  return DB_OPERATE_SUCCESS;
}


// return tag
function getTag($book_id) {
  global $g_db;
  
  $select_sql = "SELECT DISTINCT tag_name FROM b_book_tags WHERE book_id = ? ORDER BY tag_name";
  
  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($book_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
    return array();
  }
  
  $tags = array();
  if ($result) {
    foreach ($result as $row) {
      $tags[] = $row['tag_name'];
    }
  }
  
  return $tags;
}



// search book by tag
function searchByTag($tag, $user_id = null) {
  global $g_db;
  
  if (empty($tag)) return array();
  
  if ($user_id) {
    // 特定ユーザーの本を検索
    $select_sql = "SELECT DISTINCT bl.* 
                   FROM b_book_list bl 
                   INNER JOIN b_book_tags bt ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
                   WHERE bt.tag_name = ? AND bt.user_id = ? 
                   ORDER BY bl.update_date DESC";
    $params = array($tag, $user_id);
  } else {
    // 全ユーザーの本を検索（プライバシー設定を考慮）
    $select_sql = "SELECT DISTINCT bl.* 
                   FROM b_book_list bl 
                   INNER JOIN b_book_tags bt ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
                   INNER JOIN b_user u ON bt.user_id = u.user_id
                   WHERE bt.tag_name = ? AND u.diary_policy = 1
                   ORDER BY bl.update_date DESC";
    $params = array($tag);
  }

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, $params, DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
    return array();
  }
  
  return $result ?: array();
}

// get popular tags
function getPopularTags($limit = 20) {
  global $g_db;
  
  // LIMITは直接SQL文に含める（PDOのプレースホルダーはLIMIT句で問題を起こすことがある）
  $limit = intval($limit);
  
  // キャッシュテーブルが存在する場合はそちらを使用
  $cache_exists = $g_db->getOne("SHOW TABLES LIKE 'b_popular_tags_cache'");
  
  if ($cache_exists) {
    // キャッシュテーブルから取得（高速）
    $select_sql = "SELECT tag_name, user_count as tag_count 
                   FROM b_popular_tags_cache 
                   ORDER BY user_count DESC, tag_name ASC
                   LIMIT " . intval($limit);
  } else {
    // キャッシュテーブルがない場合は全ユーザーのタグを集計（公開限定なし）
    // 3500万件のデータでは公開ユーザーでのフィルタリングは現実的ではない
    $select_sql = "SELECT tag_name, COUNT(DISTINCT user_id) as tag_count 
                   FROM b_book_tags 
                   GROUP BY tag_name 
                   ORDER BY tag_count DESC, tag_name ASC
                   LIMIT " . intval($limit);
  }
  
  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array(), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
    return array();
  }
  
  return $result ?: array();
}

// get user tags
function getUserTags($book_id, $user_id) {
  global $g_db;
  
  $select_sql = "SELECT tag_name FROM b_book_tags WHERE book_id = ? AND user_id = ? ORDER BY tag_name";
  
  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($book_id, $user_id), DB_FETCHMODE_ASSOC);
  
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
    return array();
  }
  
  $tags = array();
  if ($result) {
    foreach ($result as $row) {
      $tags[] = $row['tag_name'];
    }
  }
  
  if (defined('DEBUG_MODE') && DEBUG_MODE) error_log("getUserTags() returning: " . print_r($tags, true));
  return $tags;
}

// add favorite book
function addFavoriteBook($user_id, $product_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // get current favorite book
  $sql = 'SELECT favorite_books FROM b_user WHERE user_id=?';

  if(defined('DEBUG')) { d($sql); }
  $current_books = $db->getOne($sql, array($user_id));
  if(DB::isError($current_books)) {
    trigger_error($current_books->getMessage() . '<br />');
  }
  
  // create array
  if($current_books != NULL)
    $current_books_array = explode(',', $current_books);
  else 
    $current_books_array = array();
  
  array_push($current_books_array, $product_id);
  $new_books = implode(',', $current_books_array);

  // update
  $sql = 'UPDATE b_user SET favorite_books=? WHERE user_id=?';

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($new_books, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  return DB_OPERATE_SUCCESS;
}


// remove favorite book
function removeFavoriteBook($user_id, $product_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // get current favorite book
  $sql = 'SELECT favorite_books FROM b_user WHERE user_id=?';

  if(defined('DEBUG')) { d($sql); }
  $current_books = $db->getOne($sql, array($user_id));
  if(DB::isError($current_books)) {
    trigger_error($current_books->getMessage() . '<br />');
  }
  
  // create array
  // create array
  if($current_books != NULL) {
    $current_books_array = explode(',', $current_books);

    $new_books_array = array();
  
    foreach($current_books_array as $item) {
      if($item != $product_id) {
        array_push($new_books_array, $item);
      }
    }
  
    $new_books = implode(',', $new_books_array);

  } else {
    $new_books = NULL;
  }
  

  // update
  $sql = 'UPDATE b_user SET favorite_books=? WHERE user_id=?';

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($new_books, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  return DB_OPERATE_SUCCESS;
}

// get favorite books
function getFavoriteBook($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // get current favorite book
  $sql = 'SELECT favorite_books FROM b_user WHERE user_id=?';

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->getOne($sql, array($user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage() . '<br />');
  }
  
  if($result != NULL)
    $books_array = explode(',', $result);
  else
    $books_array = '';
    
  return $books_array;
}


function getBookFromRepository($asin) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $select_sql = "select * from b_book_repository where asin=?";

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getRow($select_sql, array($asin), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


function getBookFromRepositoryRakuten($isbn) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $select_sql = "select * from b_book_repository where isbn=?";

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getRow($select_sql, array($isbn), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


function addBookToRepository($asin, $title, $image_url, $author) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $insert_array = array($asin, $title, $image_url, $author);

  // insertion
  $insert_sql = "insert into b_book_repository(asin, title, image_url, author) values (?, ?, ?, ?)";
  if(defined('DEBUG')) { d($insert_sql); }
  $result = $g_db->query($insert_sql, $insert_array);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return DB_OPERATE_SUCCESS;
}


function addBookToRepositoryRakuten($isbn, $title, $image_url, $author) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $insert_array = array($isbn, $title, $image_url, $author);

  // insertion
  $insert_sql = "insert into b_book_repository(isbn, title, image_url, author) values (?, ?, ?, ?)";
  if(defined('DEBUG')) { d($insert_sql); }
  $result = $g_db->query($insert_sql, $insert_array);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return DB_OPERATE_SUCCESS;
}



function asin2id($asin, $user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $select_sql = "select * from b_book_list where amazon_id=? and user_id=?";

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getRow($select_sql, array($asin, $user_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}



// increment referred number
function incrementReferNum($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $sql = 'UPDATE b_book_list SET number_of_refer=number_of_refer+1 WHERE book_id=?';

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($book_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return DB_OPERATE_SUCCESS;
}



function getNewReview($user_id = '', $limit = '') {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $limit_clause = '';
  if($limit != '') {
    $limit_clause = "LIMIT " . intval($limit) . "";
  }

  if($user_id != '') {
    $select_sql = "SELECT bl.*, bu.user_id, bu.diary_policy FROM b_book_list bl, b_user bu WHERE bu.user_id=? AND bl.memo<>'' AND bl.user_id=bu.user_id and bu.diary_policy=? ORDER BY memo_updated DESC, update_date DESC $limit_clause";
    if(defined('DEBUG')) { d($select_sql); }
    $result = $g_db->getAll($select_sql, array($user_id, '1'), DB_FETCHMODE_ASSOC);
  } else {
    $select_sql = "SELECT bl.*, bu.user_id, bu.diary_policy FROM b_book_list bl, b_user bu WHERE bl.memo <> '' AND bl.user_id=bu.user_id and bu.diary_policy=? ORDER BY bl.memo_updated DESC, bl.update_date DESC $limit_clause";
    if(defined('DEBUG')) { d($select_sql); }
    $result = $g_db->getAll($select_sql, array('1'), DB_FETCHMODE_ASSOC);
  }

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}


function getPopularReview($user_id = '', $limit = '') {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $min_refer = 50;
  
  $limit_clause = '';
  if($limit != '') {
    $limit_clause = "LIMIT " . intval($limit) . "";
  }

  if($user_id != '') {
    $select_sql = "SELECT bl.*, bu.user_id, bu.diary_policy FROM b_book_list bl, b_user bu WHERE bu.user_id=? AND bl.memo<>'' AND bl.number_of_refer >= " . intval($min_refer) . " AND bl.user_id=bu.user_id and bu.diary_policy=? ORDER BY bl.number_of_refer DESC $limit_clause";
    if(defined('DEBUG')) { d($select_sql); }
    $result = $g_db->getAll($select_sql, array($user_id, '1'), DB_FETCHMODE_ASSOC);
  } else {
    $select_sql = "SELECT bl.*, bu.user_id, bu.diary_policy FROM b_book_list bl, b_user bu WHERE bl.memo <> '' AND bl.user_id=bu.user_id and bu.diary_policy=? AND bl.number_of_refer >= " . intval($min_refer) . " ORDER BY bl.number_of_refer DESC $limit_clause";
    if(defined('DEBUG')) { d($select_sql); }
    $result = $g_db->getAll($select_sql, array('1'), DB_FETCHMODE_ASSOC);
  }

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}



function searchBookshelfByAuthor($user_id, $author) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $select_sql = 'SELECT bl.* FROM b_book_list bl, b_book_repository br WHERE br.author=? AND bl.user_id=? AND br.asin=bl.amazon_id ORDER BY bl.update_date DESC';

  if(defined('DEBUG')) { d($select_sql); }
  
  $result = $g_db->getAll($select_sql, array($author, $user_id), DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}

// return others event
function getNewestEvent($user_id = '') {
  global $g_db;
  // $g_db is already a DB_PDO instance
    
  if($user_id != '') {
    $get_sql = "SELECT * FROM b_book_event be, b_user bu WHERE be.user_id <> ? AND be.user_id=bu.user_id AND bu.diary_policy=? ORDER BY event_date DESC LIMIT 1";
    if(defined('DEBUG')) { d($get_sql); }
    $result = $g_db->getRow($get_sql, array($user_id, 1), DB_FETCHMODE_ASSOC);
  } else {
    $get_sql = "SELECT * FROM b_book_event be, b_user bu WHERE be.user_id=bu.user_id AND bu.diary_policy=? ORDER BY event_date DESC LIMIT 1";
    if(defined('DEBUG')) { d($get_sql); }
    $result = $g_db->getRow($get_sql, array(1), DB_FETCHMODE_ASSOC);
  }
  
  return $result;
}


function aggregateTag($user_id, $limit = '') {
  // タグ機能は無効化されました
  return array();
  $result = $g_db->getAll($get_sql, array($user_id), DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


function aggregateAllTag($limit = '') {
  // タグ機能は無効化されました
  return array();
  $result = $g_db->getAll($get_sql, array(1), DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


function searchAllBookshelfByAuthor($author) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $select_sql = 'SELECT DISTINCT bl.* FROM b_book_list bl, b_book_repository br, b_user bu WHERE br.author=? AND br.asin=bl.amazon_id AND bu.diary_policy=? AND bl.user_id=bu.user_id ORDER BY bl.update_date DESC';

  if(defined('DEBUG')) { d($select_sql); }
  
  $result = $g_db->getAll($select_sql, array($author, 1), DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}


function searchAllBookshelfByTag($tag) {
  // タグ機能は無効化されました
  return array();
  
  $result = $g_db->getAll($select_sql, array($tag, 1), DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;

}


// add comment
function createComment($book_id, $from_user, $comment) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // get target book information
  $target_book = getBookInformation($book_id);
  if($target_book == NULL) {
    return DB_OPERATE_SUCCESS;
  }
  
  $target_book_user = $target_book['user_id'];
  $unread = 1;
  
  // escape comment
  $comment = html($comment);
  
  $insert_array = array($comment, $target_book_user, $from_user, $book_id, $unread);
  
  // Use NOW() instead of UNIX_TIMESTAMP() to avoid 2038 problem
  $add_sql = "INSERT INTO b_book_comment(comment, to_user, from_user, created, book_id, unread) VALUES (?, ?, ?, NOW(), ?, ?)";
  if(defined('DEBUG')) { d($add_sql); }
  $result = $g_db->query($add_sql, $insert_array);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  return DB_OPERATE_SUCCESS;
}


// delete comment
function deleteComment($comment_id, $user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // confirmation
  $get_sql = "SELECT * FROM b_book_comment WHERE id=?";
  if(defined('DEBUG')) { d($get_sql); }
  $result = $g_db->getRow($get_sql, array($comment_id), DB_FETCHMODE_ASSOC);
  
  if($result == NULL) return DB_OPERATE_SUCCESS;
  if($user_id != $result['from_user'] && $user_id != $result['to_user']) return DB_OPERATE_SUCCESS;
  
  $delete_sql = "DELETE FROM b_book_comment WHERE id=?";
  if(defined('DEBUG')) { d($delete_sql); }
  $result = $g_db->query($delete_sql, array($comment_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  return DB_OPERATE_SUCCESS;
}


// delete comment
function updateComment($comment_id, $comment) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  // escape comment
  $comment = html($comment);
  
  $update_sql = "UPDATE b_book_comment SET comment=? WHERE id=?";
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array($comment, $comment_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  return DB_OPERATE_SUCCESS;
}

// get book comments
function getComment($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $get_sql = "SELECT * FROM b_book_comment WHERE book_id=?";
  if(defined('DEBUG')) { d($get_sql); }
  $result = $g_db->getAll($get_sql, array($book_id), DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return $result;
}


// get book comments unread
function getUnreadComment($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $get_sql = "SELECT COUNT(book_id) AS book_comment, book_id FROM b_book_comment WHERE unread=1 AND to_user=? AND from_user<>? GROUP BY book_id";
  if(defined('DEBUG')) { d($get_sql); }
  $result = $g_db->getAll($get_sql, array($user_id, $user_id), DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return $result;
}


// get book comments unread
function setCommentRead($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $set_sql = "UPDATE b_book_comment SET unread=0 WHERE book_id=?";
  if(defined('DEBUG')) { d($set_sql); }
  $result = $g_db->query($set_sql, array($book_id));

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return DB_OPERATE_SUCCESS;
}


// get disclosed book_shelf
function getAllBookshelf($limit = '') {
  global $g_db;
  // $g_db is already a DB_PDO instance

  if($limit != '') {
    $limit_clause = " LIMIT " . intval($limit) . " ";
  } else {
    $limit_clause = '';
  }

  $select_sql = "SELECT count(br.author) AS author_count, br.author, MAX(bl.update_date) AS newest_date FROM b_book_list bl, b_user bu, b_book_repository br WHERE bl.user_id=bu.user_id AND bl.amazon_id=br.asin AND bu.diary_policy=1 GROUP BY br.author ORDER BY newest_date DESC $limit_clause";

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, NULL, DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


function updateSakkaCloud() {
  global $g_db;
  // $g_db is already a DB_PDO instance

 $select_sql = "SELECT count(br.author) AS author_count, br.author, MAX(bl.update_date) AS newest_date FROM b_book_list bl, b_user bu, b_book_repository br WHERE bl.user_id=bu.user_id AND bl.amazon_id=br.asin AND bu.diary_policy=1 GROUP BY br.author ORDER BY newest_date DESC";

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, NULL, DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  $author_count = count($result);
  
  $delete_sql = "DELETE FROM b_sakka_cloud";
  if(defined('DEBUG')) { d($delete_sql); }
    $delete_result = $db->query($delete_sql);
    if(DB::isError($delete_result)) {
    trigger_error($delete_result->getMessage());
  }
  
  for($j = 0; $j < $author_count; $j++) {
    $count = $result[$j]['author_count'];
    $author = $result[$j]['author'];
    $newest_date = $result[$j]['newest_date'];
    
    if($author == '') continue;
    
    $insert_array = array($author, $count, $newest_date);
    $add_sql = "INSERT INTO b_sakka_cloud(author, author_count, updated) VALUES (?, ?, ?)";

    if(defined('DEBUG')) { d($add_sql); }
      $insert_result = $db->query($add_sql, $insert_array);
      if(DB::isError($insert_result)) {
      trigger_error($insert_result->getMessage());
    }
  }
}


function getStoredSakkaCloud($limit = "") {
  global $g_db;
  // $g_db is already a DB_PDO instance

  if($limit != '') {
    $limit_clause = " LIMIT " . intval($limit) . " ";
  } else {
    $limit_clause = '';
  }

  $select_sql = "SELECT * FROM b_sakka_cloud ORDER BY updated DESC $limit_clause";

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, NULL, DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}



/**
 * グローバル検索機能
 * 全ユーザーの公開本棚から本を検索
 * 
 * @param string $keyword 検索キーワード
 * @param string $search_type 検索タイプ (all, title, author, isbn)
 * @param int $page ページ番号
 * @param int $limit 1ページあたりの件数
 * @param array $options 追加オプション (status, rating_min, year等)
 * @return array 検索結果と総件数
 */
function globalSearchBooks($keyword, $search_type = 'all', $page = 1, $limit = 20, $options = []) {
  global $g_db;
  
  // キーワードの正規化
  $keyword = trim($keyword);
  if (empty($keyword) && $search_type !== 'recent') {
    return ['books' => [], 'total' => 0, 'page' => $page, 'limit' => $limit];
  }
  
  // ページネーション設定
  $offset = ($page - 1) * $limit;
  
  // キャッシュキーの生成
  require_once(dirname(__FILE__) . '/cache.php');
  $cache = getCache();
  $cache_key = 'global_search_' . md5($keyword . '_' . $search_type . '_' . $page . '_' . $limit . '_' . serialize($options));
  
  // キャッシュチェック（5分間有効）
  $cached_result = $cache->get($cache_key);
  if ($cached_result !== false) {
    return $cached_result;
  }
  
  // 基本SQL構築（本単位でグループ化）
  // タイトル+著者でグループ化（ASIN/ISBNが異なる版も統合）
  $select_sql = "
    SELECT 
      CONCAT(bl.name, '::::', bl.author) as book_key,
      MIN(bl.book_id) as book_id,
      MIN(bl.user_id) as sample_user_id,
      bl.name AS title,
      bl.author,
      GROUP_CONCAT(DISTINCT bl.isbn) as isbn,
      GROUP_CONCAT(DISTINCT bl.amazon_id) as asin,
      MAX(bl.image_url) as image_url,
      MAX(bl.total_page) as total_page,
      COUNT(DISTINCT bl.user_id) as reader_count,
      AVG(CASE WHEN bl.rating > 0 THEN bl.rating END) as avg_rating,
      MAX(bl.update_date) as last_update,
      GROUP_CONCAT(DISTINCT 
        CASE 
          WHEN bl.status = 1 THEN '読みたい'
          WHEN bl.status = 2 THEN '読んでる'
          WHEN bl.status = 3 THEN '読了'
          WHEN bl.status = 4 THEN '既読'
        END
      ) as status_summary,
      COUNT(CASE WHEN bl.status IN (3, 4) THEN 1 END) as finished_count,
      COUNT(CASE WHEN bl.status = 2 THEN 1 END) as reading_count,
      COUNT(CASE WHEN bl.status = 1 THEN 1 END) as want_count,
      SUBSTRING(
        GROUP_CONCAT(
          CASE 
            WHEN bl.memo IS NOT NULL AND bl.memo != '' AND bl.rating >= 4
            THEN bl.memo
          END
          ORDER BY bl.rating DESC, LENGTH(bl.memo) DESC
          SEPARATOR ' / '
        ), 1, 300
      ) as review_excerpts,
      COUNT(CASE WHEN bl.memo IS NOT NULL AND bl.memo != '' THEN 1 END) as review_count
    FROM b_book_list bl
    INNER JOIN b_user u ON bl.user_id = u.user_id
    WHERE u.diary_policy = 1  -- 公開設定のユーザーのみ
      AND u.status = 1  -- アクティブユーザー
      AND bl.status > 0  -- 有効な本（1:読みたい, 2:読んでる, 3:読了, 4:既読）
  ";
  
  // 検索条件の追加
  $params = [];
  
  if (!empty($keyword)) {
    switch ($search_type) {
      case 'title':
        $select_sql .= " AND bl.name LIKE ?";
        $params[] = '%' . $keyword . '%';
        break;
        
      case 'author':
        $select_sql .= " AND bl.author LIKE ?";
        $params[] = '%' . $keyword . '%';
        break;
        
      case 'isbn':
        // ISBNはハイフンを除去して検索
        $clean_isbn = str_replace(['-', ' '], '', $keyword);
        $select_sql .= " AND REPLACE(REPLACE(bl.isbn, '-', ''), ' ', '') = ?";
        $params[] = $clean_isbn;
        break;
        
      case 'all':
      default:
        $select_sql .= " AND (bl.name LIKE ? OR bl.author LIKE ?)";
        $params[] = '%' . $keyword . '%';
        $params[] = '%' . $keyword . '%';
        break;
    }
  }
  
  // 追加フィルタ
  if (isset($options['status']) && $options['status'] !== '') {
    $select_sql .= " AND bl.status = ?";
    $params[] = $options['status'];
  }
  
  if (isset($options['rating_min']) && $options['rating_min'] > 0) {
    $select_sql .= " AND bl.rating >= ?";
    $params[] = $options['rating_min'];
  }
  
  if (isset($options['year']) && $options['year'] !== '') {
    $select_sql .= " AND YEAR(bl.finished_date) = ?";
    $params[] = $options['year'];
  }
  
  // GROUP BY句を追加（タイトル+著者でグループ化）
  $select_sql .= " GROUP BY bl.name, bl.author";
  
  // ORDER BY
  $order_by = " ORDER BY ";
  switch ($options['sort'] ?? 'relevance') {
    case 'recent':
      $order_by .= "last_update DESC";
      break;
    case 'rating':
    case 'rating_desc':
      $order_by .= "avg_rating DESC, reader_count DESC";
      break;
    case 'rating_asc':
      $order_by .= "avg_rating ASC, reader_count ASC";
      break;
    case 'popular':
    case 'readers_desc':
      $order_by .= "reader_count DESC, avg_rating DESC";
      break;
    case 'readers_asc':
      $order_by .= "reader_count ASC, avg_rating ASC";
      break;
    case 'title':
    case 'title_asc':
      $order_by .= "title ASC";
      break;
    case 'title_desc':
      $order_by .= "title DESC";
      break;
    case 'author':
    case 'author_asc':
      $order_by .= "bl.author ASC, title ASC";
      break;
    case 'author_desc':
      $order_by .= "bl.author DESC, title DESC";
      break;
    default: // relevance
      if (!empty($keyword)) {
        // キーワードとの関連度でソート（タイトル完全一致を優先）
        $order_by .= "
          CASE 
            WHEN title = " . $g_db->quote($keyword) . " THEN 1
            WHEN title LIKE " . $g_db->quote($keyword . '%') . " THEN 2
            WHEN title LIKE " . $g_db->quote('%' . $keyword . '%') . " THEN 3
            ELSE 4
          END,
          reader_count DESC,
          avg_rating DESC
        ";
      } else {
        $order_by .= "reader_count DESC";
      }
      break;
  }
  
  $select_sql .= $order_by;
  
  // 総件数を取得（LIMIT前）- タイトル+著者でカウント
  $count_sql = "
    SELECT COUNT(DISTINCT CONCAT(bl.name, '::::', bl.author)) as total
    FROM b_book_list bl
    INNER JOIN b_user u ON bl.user_id = u.user_id
    WHERE u.diary_policy = 1
      AND u.status = 1
      AND bl.status > 0
  ";
  
  // カウント用にも同じ検索条件を適用
  $count_params = [];
  if (!empty($keyword)) {
    switch ($search_type) {
      case 'title':
        $count_sql .= " AND bl.name LIKE ?";
        $count_params[] = '%' . $keyword . '%';
        break;
      case 'author':
        $count_sql .= " AND bl.author LIKE ?";
        $count_params[] = '%' . $keyword . '%';
        break;
      case 'isbn':
        $clean_isbn = str_replace(['-', ' '], '', $keyword);
        $count_sql .= " AND REPLACE(REPLACE(bl.isbn, '-', ''), ' ', '') = ?";
        $count_params[] = $clean_isbn;
        break;
      case 'all':
      default:
        $count_sql .= " AND (bl.name LIKE ? OR bl.author LIKE ?)";
        $count_params[] = '%' . $keyword . '%';
        $count_params[] = '%' . $keyword . '%';
        break;
    }
  }
  
  if (isset($options['status']) && $options['status'] !== '') {
    $count_sql .= " AND bl.status = ?";
    $count_params[] = $options['status'];
  }
  
  if (isset($options['rating_min']) && $options['rating_min'] > 0) {
    $count_sql .= " AND bl.rating >= ?";
    $count_params[] = $options['rating_min'];
  }
  
  if (isset($options['year']) && $options['year'] !== '') {
    $count_sql .= " AND YEAR(bl.finished_date) = ?";
    $count_params[] = $options['year'];
  }
  
  $total = $g_db->getOne($count_sql, $count_params);
  if (DB::isError($total)) {
    error_log("Global search count error: " . $total->getMessage());
    $total = 0;
  }
  
  // LIMIT追加（直接SQLに埋め込む）
  $select_sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
  
  // 検索実行
  $books = $g_db->getAll($select_sql, $params, DB_FETCHMODE_ASSOC);
  if (DB::isError($books)) {
    error_log("Global search error: " . $books->getMessage());
    $books = [];
  }
  
  // レビュー検索も実行（キーワードがある場合）
  $reviews = [];
  if (!empty($keyword) && ($search_type === 'all' || $search_type === 'review')) {
    $review_sql = "
      SELECT 
        bl.book_id,
        bl.name AS title,
        bl.author,
        bl.isbn,
        bl.amazon_id AS asin,
        bl.image_url,
        bl.memo AS review_text,
        bl.rating,
        bl.update_date,
        u.nickname AS reviewer_name,
        u.user_id AS reviewer_id,
        LENGTH(bl.memo) as review_length
      FROM b_book_list bl
      INNER JOIN b_user u ON bl.user_id = u.user_id
      WHERE u.diary_policy = 1
        AND u.status = 1
        AND bl.memo IS NOT NULL
        AND bl.memo != ''
        AND LENGTH(bl.memo) >= 50
        AND bl.memo LIKE ?
      ORDER BY 
        CASE 
          WHEN bl.memo LIKE ? THEN 1
          ELSE 2
        END,
        bl.rating DESC,
        LENGTH(bl.memo) DESC,
        bl.update_date DESC
      LIMIT 10
    ";
    
    $review_params = [
      '%' . $keyword . '%',
      $keyword . '%'
    ];
    
    $reviews = $g_db->getAll($review_sql, $review_params, DB_FETCHMODE_ASSOC);
    if (DB::isError($reviews)) {
      error_log("Review search error: " . $reviews->getMessage());
      $reviews = [];
    }
  }
  
  // 結果を整形
  $result = [
    'books' => $books,
    'reviews' => $reviews,
    'total' => (int)$total,
    'total_reviews' => count($reviews),
    'page' => $page,
    'limit' => $limit,
    'total_pages' => ceil($total / $limit),
    'keyword' => $keyword,
    'search_type' => $search_type
  ];
  
  // キャッシュに保存（5分間）
  $cache->set($cache_key, $result, 300);
  
  return $result;
}

/**
 * 特定の本のトップレビューを取得
 * 
 * @param string $book_key ISBNまたはタイトル+著者の組み合わせ
 * @param int $limit 取得件数
 * @return array
 */
function getBookTopReviews($book_key, $limit = 3) {
  global $g_db;
  
  // book_keyからISBNまたはタイトル+著者を抽出
  $where_condition = "";
  $params = [];
  
  if (strpos($book_key, '::::') !== false) {
    // タイトル+著者の組み合わせ
    list($title, $author) = explode('::::', $book_key, 2);
    $where_condition = " AND bl.name = ? AND bl.author = ?";
    $params[] = $title;
    $params[] = $author;
  } else {
    // ISBN
    $where_condition = " AND bl.isbn = ?";
    $params[] = $book_key;
  }
  
  $sql = "
    SELECT 
      bl.memo as review_text,
      bl.rating,
      bl.status,
      u.nickname as reviewer_name,
      bl.update_date as review_date,
      bl.user_id
    FROM b_book_list bl
    INNER JOIN b_user u ON bl.user_id = u.user_id
    WHERE u.diary_policy = 1
      AND u.status = 1
      AND bl.memo IS NOT NULL
      AND bl.memo != ''
      AND LENGTH(bl.memo) >= 50  -- 短すぎるレビューは除外
      $where_condition
    ORDER BY 
      bl.rating DESC,
      LENGTH(bl.memo) DESC,
      bl.update_date DESC
    LIMIT ?
  ";
  
  $params[] = $limit;
  
  $reviews = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
  if (DB::isError($reviews)) {
    error_log("Get book reviews error: " . $reviews->getMessage());
    return [];
  }
  
  return $reviews;
}

/**
 * 人気の本を取得（グローバル）
 * 
 * @param int $limit 取得件数
 * @param string $period 期間 (all, year, month, week)
 * @return array
 */
function getPopularBooksGlobal($limit = 10, $period = 'month') {
  global $g_db;
  
  $date_condition = "";
  switch ($period) {
    case 'week':
      $date_condition = " AND bl.update_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
      break;
    case 'month':
      $date_condition = " AND bl.update_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
      break;
    case 'year':
      $date_condition = " AND bl.update_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
      break;
  }
  
  $sql = "
    SELECT 
      bl.name AS title,
      bl.author,
      bl.isbn,
      bl.author AS publisher,
      bl.image_url,
      COUNT(DISTINCT bl.user_id) as reader_count,
      AVG(CASE WHEN bl.rating > 0 THEN bl.rating END) as avg_rating,
      MAX(bl.update_date) as last_update
    FROM b_book_list bl
    INNER JOIN b_user u ON bl.user_id = u.user_id
    WHERE u.diary_policy = 1
      AND u.status = 1
      AND bl.status IN (3, 4)  -- 読了または既読
      $date_condition
    GROUP BY 
      CASE 
        WHEN bl.isbn != '' THEN bl.isbn
        ELSE CONCAT(bl.name, '|||', bl.author)
      END
    HAVING reader_count >= 2  -- 最低2人が読んでいる
    ORDER BY reader_count DESC, avg_rating DESC
    LIMIT ?
  ";
  
  $books = $g_db->getAll($sql, [$limit], DB_FETCHMODE_ASSOC);
  if (DB::isError($books)) {
    error_log("Popular books error: " . $books->getMessage());
    return [];
  }
  
  return $books;
}

function createToc($limit = '') {
  global $g_db;
  // $g_db is already a DB_PDO instance

  if($limit != '') {
    $limit_clause = " LIMIT " . intval($limit) . " ";
  } else {
    $limit_clause = '';
  }

  //$sql = "SET NAMES utf8";
  //$result = $g_db->query($sql);

  //$select_sql = "SELECT SUBSTRING(LTRIM(name), 1, 2) AS ltr, MIN(name) AS fst, MAX(name) AS lst, COUNT(*) AS cnt FROM b_book_list GROUP BY SUBSTRING(LTRIM(name), 1, 2) ORDER BY 1 $limit_clause";
  $select_sql = "SELECT LEFT(LTRIM(name), 1) AS ltr, MIN(name) AS fst, MAX(name) AS lst, COUNT(*) AS cnt FROM b_book_list GROUP BY LEFT(LTRIM(name), 1) ORDER BY 1 $limit_clause";

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, NULL, DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}

function getRelatedBooks($book_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  // get book tag
  $tags = getTag($book_id);
  $tag_count = count($tags);
  
  if($tag_count == 0)
    return NULL;
  
  $tag_array = array();
  for($i = 0; $i < $tag_count; $i++) {
    array_push($tag_array, $tags[$i]['tag']);
  }
  
  $tag_list = join(' ', $tag_array);
  //d($tag_list);
  
  $select_sql = 'SELECT book_id, name, MATCH (name) AGAINST (? IN boolean MODE) AS score FROM b_book_list WHERE MATCH (name) AGAINST (? IN boolean MODE) ORDER BY score DESC';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($tag_list, $tag_list), DB_FETCHMODE_ASSOC);

  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


function aggregateUserTag($user_id, $limit = '') {
  global $g_db;
  
  // パフォーマンス最適化：EXISTS句を使用してJOINを回避
  $sql = "SELECT tag_name, COUNT(DISTINCT book_id) as tag_count 
          FROM b_book_tags 
          WHERE user_id = ? 
          AND EXISTS (
              SELECT 1 FROM b_book_list 
              WHERE b_book_list.book_id = b_book_tags.book_id 
              AND b_book_list.user_id = b_book_tags.user_id
          )
          GROUP BY tag_name 
          ORDER BY tag_count DESC, tag_name ASC";
  
  if ($limit !== '' && is_numeric($limit)) {
    $sql .= " LIMIT " . intval($limit);
  }
  
  $result = $g_db->getAll($sql, array($user_id), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
    return array();
  }
  
  return $result;
}


// update tokens
function updateToken($user_id, $token, $secret_token) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  if($user_id == '') return;
  
  $update_sql = 'UPDATE b_user SET access_token=?, access_token_secret=? WHERE user_id=?';

  if(defined('DEBUG')) { 
    d($update_sql);
  }
  $result = $g_db->query($update_sql, array($token, $secret_token, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return DB_OPERATE_SUCCESS;
}


// get announcements
function getAnnouncement($id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  // タイプカラムが存在するかチェック
  $check_type_column = $g_db->getOne("SHOW COLUMNS FROM b_announcement LIKE 'type'");
  
  if($id == '') {
    if ($check_type_column) {
      $select_sql = 'SELECT * FROM b_announcement ORDER BY created DESC, id DESC';
    } else {
      $select_sql = 'SELECT *, "general" as type FROM b_announcement ORDER BY created DESC, id DESC';
    }
    $result = $g_db->getAll($select_sql, NULL, DB_FETCHMODE_ASSOC);
  } else {
    if ($check_type_column) {
      $select_sql = 'SELECT * FROM b_announcement WHERE id=?';
    } else {
      $select_sql = 'SELECT *, "general" as type FROM b_announcement WHERE id=?';
    }
    $result = $g_db->getRow($select_sql, array($id), DB_FETCHMODE_ASSOC);
  }

  if(defined('DEBUG')) { 
    d($select_sql);
  }
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}

// add announcement
function createAnnouncement($title, $content, $created) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  if($created == '') $created = date('Y-m-d H:i:s');
    
  $insert_array = array($title, $content, $created);
  $add_sql = "INSERT INTO b_announcement(title, content, created) VALUES (?, ?, ?)";

  if(defined('DEBUG')) { d($add_sql); }
  $result = $g_db->query($add_sql, $insert_array);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  return DB_OPERATE_SUCCESS;

}


// delete announcement
function deleteAnnouncement($id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  if($id == '') return;
    
  $delete_sql = "DELETE FROM b_announcement WHERE id=?";

  if(defined('DEBUG')) { d($delete_sql); }
  $result = $g_db->query($delete_sql, array($id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  return DB_OPERATE_SUCCESS;
}


// add announcement
function updateAnnouncement($id, $title, $content, $created) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  if($created == '') $created = date('Y-m-d H:i:s');
    
  $update_array = array($title, $content, $created, $id);
  $update_sql = "UPDATE b_announcement SET title=?, content=?, created=? WHERE id=?";

  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, $update_array);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  return DB_OPERATE_SUCCESS;

}



// update event
function updateEvent($user_id, $event_id, $memo, $total_page) {
  global $g_db;
  // $g_db is already a DB_PDO instance
  

  return DB_OPERATE_SUCCESS;
}

// remove event
function removeEvent($user_id, $event_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance
      
  $sql = "DELETE FROM b_book_event WHERE id=? AND user_id=?";

  if(defined('DEBUG')) { d($sql); }
  $result = $g_db->query($sql, array($event_id, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return DB_OPERATE_SUCCESS;
}


// update user's read num
function updateUserReadingStat($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  // DATETIME形式に対応
  $month_start = date('Y-m-01 00:00:00');
  $month_end = date('Y-m-t 23:59:59');

  // total
  $select_sql = 'select count(*) from b_book_list where user_id=? and (status=? or status=?)';
  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($user_id, READING_FINISH, READ_BEFORE));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  $read_book_total = $result;
  
  // this month - イベントからカウント（DISTINCTでbook_idの重複を避ける）
  $select_sql = 'select count(DISTINCT book_id) from b_book_event where user_id=? and (event_date between ? and ?) and event=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($user_id, $month_start, $month_end, READING_FINISH));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  $read_book_month_events = $result;
  
  // this month - finished_dateからカウント（READ_BEFOREも含む）
  // 重複を避けるため、finished_dateがある本でイベントがない本のみカウント
  $select_sql = 'select count(DISTINCT bl.book_id) from b_book_list bl
                 WHERE bl.user_id=? 
                 AND bl.finished_date >= DATE(?) 
                 AND bl.finished_date <= DATE(?)
                 AND bl.status IN (?, ?)
                 AND NOT EXISTS (
                   SELECT 1 FROM b_book_event be 
                   WHERE be.user_id = bl.user_id 
                   AND be.book_id = bl.book_id 
                   AND be.event = ?
                   AND be.event_date between ? and ?
                 )';
  
  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array(
    $user_id, 
    $month_start, $month_end, 
    READING_FINISH, READ_BEFORE,
    READING_FINISH,
    $month_start, $month_end
  ));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  $read_book_month_finished = $result ?: 0;
  
  // 両方の合計
  $read_book_month = $read_book_month_events + $read_book_month_finished;


  /*
  $select_sql = 'select sum(total_page) from b_book_list where user_id=? and (status=? or status=?)';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($user_id, READING_FINISH, READ_BEFORE));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  $read_page_num = $result;
  */
  
  $update_sql = 'update b_user set read_books_total=?, read_books_month=? where user_id=?';
  if(defined('DEBUG')) { d($update_sql); }
  $result = $g_db->query($update_sql, array($read_book_total, $read_book_month, $user_id));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return DB_OPERATE_SUCCESS;
}


// get users order by reading achievement
function getUserRanking($order_key) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  // ホワイトリスト方式で許可されたカラム名のみを受け付ける
  $allowed_columns = [
    'read_books_total',
    'read_books_month',
    'read_pages_total',
    'read_pages_month',
    'read_days_total',
    'read_days_month'
  ];
  
  // 許可されたカラムかチェック
  if (!in_array($order_key, $allowed_columns, true)) {
    trigger_error("Invalid order key: " . $order_key);
    return array();
  }
  
  // 月間統計の場合はリアルタイムで集計
  if ($order_key === 'read_books_month') {
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');
    
    $select_sql = "SELECT 
                     u.user_id, 
                     u.nickname, 
                     u.photo, 
                     u.photo_state, 
                     u.diary_policy,
                     COUNT(DISTINCT be.book_id) as `$order_key`
                   FROM b_user u
                   LEFT JOIN b_book_event be ON u.user_id = be.user_id 
                     AND be.event = " . READING_FINISH . "
                     AND be.event_date BETWEEN ? AND ?
                   WHERE u.diary_policy = 1
                   AND u.status = 1
                   GROUP BY u.user_id
                   HAVING `$order_key` > 0
                   ORDER BY `$order_key` DESC 
                   LIMIT 200";
    
    $result = $g_db->getAll($select_sql, array($month_start, $month_end), DB_FETCHMODE_ASSOC);
  } else {
    // その他の統計は既存のカラムを使用
    $select_sql = "SELECT user_id, nickname, photo, photo_state, diary_policy, `$order_key` 
                   FROM b_user 
                   WHERE `$order_key` > 0 
                   AND diary_policy = 1
                   AND status = 1
                   ORDER BY `$order_key` DESC 
                   LIMIT 200";
    
    $result = $g_db->getAll($select_sql, NULL, DB_FETCHMODE_ASSOC);
  }

  if(defined('DEBUG')) { 
    d($select_sql);
  }
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}

/**
 * 読書傾向分析を保存
 * 
 * @param string $user_id ユーザーID
 * @param string $analysis_type 分析タイプ（trend, challenge等）
 * @param string $analysis_content 分析結果（Markdown形式）
 * @param int $is_public 公開フラグ（0:非公開, 1:公開）
 * @return int|false 保存成功時はanalysis_id、失敗時はfalse
 */
function saveReadingAnalysis($user_id, $analysis_type, $analysis_content, $is_public = 0) {
    global $g_db;
    
    $sql = "INSERT INTO b_reading_analysis (user_id, analysis_type, analysis_content, is_public) 
            VALUES (?, ?, ?, ?)";
    
    $result = $g_db->query($sql, array($user_id, $analysis_type, $analysis_content, $is_public));
    
    if (DB::isError($result)) {
        error_log('Failed to save reading analysis: ' . $result->getMessage());
        return false;
    }
    
    // 最後に挿入されたIDを取得
    $id = $g_db->getOne("SELECT LAST_INSERT_ID()");
    return $id;
}

/**
 * ユーザーの最新の読書傾向分析を取得
 * 
 * @param string $user_id ユーザーID
 * @param string $analysis_type 分析タイプ（省略時は全タイプ）
 * @return array|false 分析結果、見つからない場合はfalse
 */
function getLatestReadingAnalysis($user_id, $analysis_type = '') {
    global $g_db;
    
    if ($analysis_type) {
        $sql = "SELECT * FROM b_reading_analysis 
                WHERE user_id = ? AND analysis_type = ? 
                ORDER BY created_at DESC 
                LIMIT 1";
        $result = $g_db->getRow($sql, array($user_id, $analysis_type), DB_FETCHMODE_ASSOC);
    } else {
        $sql = "SELECT * FROM b_reading_analysis 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1";
        $result = $g_db->getRow($sql, array($user_id), DB_FETCHMODE_ASSOC);
    }
    
    if (DB::isError($result)) {
        error_log('Failed to get reading analysis: ' . $result->getMessage());
        return false;
    }
    
    return $result ?: false;
}

/**
 * 読書傾向分析の公開設定を更新
 * 
 * @param int $analysis_id 分析ID
 * @param string $user_id ユーザーID（権限確認用）
 * @param int $is_public 公開フラグ（0:非公開, 1:公開）
 * @return bool 成功時true、失敗時false
 */
function updateReadingAnalysisVisibility($analysis_id, $user_id, $is_public) {
    global $g_db;
    
    $sql = "UPDATE b_reading_analysis 
            SET is_public = ? 
            WHERE analysis_id = ? AND user_id = ?";
    
    $result = $g_db->query($sql, array($is_public, $analysis_id, $user_id));
    
    if (DB::isError($result)) {
        error_log('Failed to update reading analysis visibility: ' . $result->getMessage());
        return false;
    }
    
    return $g_db->affectedRows() > 0;
}

function getBooksUserReadInThisMonth($user_id) {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $date = date('Y n d');
  $date = explode(' ', $date);

  //明示的に変数を整数型へ変換
  $date[MONTH] = (int) $date[MONTH];
  $date[YEAR] = (int) $date[YEAR];
  $date[DAY] = (int) $date[DAY];

  //今月の日数、最初の日、最後の日の曜日を得る
  $days = date('d', mktime(0, 0, 0, $date[MONTH]+1, 0, $date[YEAR]));

  $start_time = mktime(0, 0, 0, $date[MONTH], 1, $date[YEAR]);
  $end_time = mktime(23, 59, 59, $date[MONTH], $days, $date[YEAR]);  

  // this month
  $select_sql = 'select book_id from b_book_event where user_id=? and (event_date between ? and ?) and event=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getAll($select_sql, array($user_id, $start_time, $end_time, READING_FINISH), DB_FETCHMODE_ASSOC);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }

  return $result;
}


function getUsers() {
  global $g_db;
  // $g_db is already a DB_PDO instance

  $select_sql = "SELECT user_id FROM b_user";
  $result = $g_db->getAll($select_sql, NULL, DB_FETCHMODE_ASSOC);

  if(defined('DEBUG')) { 
    d($select_sql);
  }
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}


// get user number
function getUserStat() {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select count(user_id) from b_user where regist_date is not null';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, NULL);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}

function getBookStat() {
  global $g_db;
  // $g_db is already a DB_PDO instance
  
  $select_sql = 'select count(book_id) from b_book_list';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, NULL);
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  return $result;
}

/**
 * プロフィール写真を保存
 * @param string $user_id ユーザーID
 * @param string $photo_data 画像データ
 * @param string $mime_type MIMEタイプ
 * @return int 成功時は1
 */
function saveProfilePhoto($user_id, $photo_data, $mime_type = 'image/jpeg') {
  global $g_db;
  
  // 画像のMIMEタイプを正規化
  $allowed_mimes = array(
    'image/jpeg' => 'image/jpeg',
    'image/jpg' => 'image/jpeg',
    'image/png' => 'image/png',
    'image/gif' => 'image/gif'
  );
  
  if (!isset($allowed_mimes[$mime_type])) {
    return false;
  }
  
  $mime_type = $allowed_mimes[$mime_type];
  
  // 更新（既存の写真の有無に関わらず同じSQL）
  $update_sql = 'UPDATE b_user SET photo = ?, photo_state = ?, photo_mime = ? WHERE user_id = ?';
  $result = $g_db->query($update_sql, array($photo_data, PHOTO_REGISTER_STATE, $mime_type, $user_id));
  
  if (DB::isError($result)) {
    trigger_error($result->getMessage());
    return false;
  }
  
  return DB_OPERATE_SUCCESS;
}

/**
 * プロフィール写真を削除
 * @param string $user_id ユーザーID
 * @return int 成功時は1
 */
function deleteProfilePhoto($user_id) {
  global $g_db;
  
  $update_sql = 'UPDATE b_user SET photo = NULL, photo_state = ? WHERE user_id = ?';
  $result = $g_db->query($update_sql, array(PHOTO_DELETE_STATE, $user_id));
  
  if (DB::isError($result)) {
    trigger_error($result->getMessage());
    return false;
  }
  
  return DB_OPERATE_SUCCESS;
}


?>