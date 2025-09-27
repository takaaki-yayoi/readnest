function addToShelf(asin, product_name) {
  location.href = 'add_book.php?asin=' + asin + '&product_name=' + product_name ;
}

function openBook(id) {
  var target = document.getElementById('row_' + id);
  target.style.display = 'block';

  var target = document.getElementById('close_' + id);
  target.style.display = 'block';

  var target = document.getElementById('open_' + id);
  target.style.display = 'none';
}

function cancel(id) {
  var target = document.getElementById('row_' + id);
  target.style.display = 'none';

  var target = document.getElementById('close_' + id);
  target.style.display = 'none';

  var target = document.getElementById('open_' + id);
  target.style.display = 'block';

}

function deleteBook(title, target) {
  if(window.confirm('「' + title + '」を削除しても良いですか？')) {
    var target_form = document.getElementById(target);
	target_form.submit();
  } else {
    return false;
  }
}

// コメント機能は無効化されました
// function deleteComment(title, target) {
//   if(window.confirm('コメント「' + title + '」を削除しても良いですか？')) {
//     var target_form = document.getElementById(target);
// 	target_form.submit();
//   } else {
//     return false;
//   }
// }


function finishBook(title, target) {
  if(window.confirm('「' + title + '」を読了しても良いですか？')) {
    var target_form = document.getElementById(target);
	target_form.submit();
  } else {
    return false;
  }
}



function redraw_progress_bar(id, target, total_num, percent) {

  //var current_percent = Math.ceil(target / total_num * 100);

  if(enable_pager == false) return;
  
  var target_form = document.getElementById('progress_box_' + id);
  target_form.page_list.value = target;
  
  if(target == total_num) {
    target_form.submit_button.value = '読み終えました！';
  } else {
    target_form.submit_button.value = target + 'ページまで読みました！';
  }

  var target_ratio = document.getElementById('page_' + id);

  //if (target == total_num) {
  if (percent == 100) {
    target_ratio.style.color = 'red';
    target_ratio.innerHTML = percent + '%';
  } else {
    target_ratio.style.color = 'gray';
    
	if(percent < 10) {
	  target_ratio.innerHTML = '<span style="color:white">00</span>' + percent + '%';
	} else {
	  target_ratio.innerHTML = '<span style="color:white">0</span>' + percent + '%';
    }
  }

  for(i = 1; i <= percent; i++) {
    target_element = document.getElementById('progress_' + id + '_' + i);
    target_element.style.color = 'red';
  }
  
  if (target == total_num) return;
  
  start = percent + 1;
  
  for(i = start; i <= 100; i++) {
    target_element = document.getElementById('progress_' + id + '_' + i);
    target_element.style.color = 'gray';
  }
}

function minus_page(id) {
  var target_form = document.getElementById('progress_box_' + id);
  target = target_form.page_list.value;

   var target_page = document.getElementById('page_' + id);
  
  if(target > 0) {
    current_page = target - 1;

    target_form.page_list.value = current_page;
    target_page.innerHTML = current_page;

    target_form.submit_button.value = current_page + 'ページまで読みました！';
  }
}

function plus_page(id, total_num) {
  var target_form = document.getElementById('progress_box_' + id);
  target = target_form.page_list.value;
  
   var target_page = document.getElementById('page_' + id);
  
  //alert(target + ':' + total_num);

  if(target < total_num) {
    current_page = parseInt(target) + 1;

    target_form.page_list.value = current_page;
    target_page.innerHTML = current_page;

    if(current_page == total_num) {
      target_form.submit_button.value = '読み終えました！';
    } else {
      target_form.submit_button.value = current_page + 'ページまで読みました！';
    }
  }
}

function plus_page2(id, total_num, step) {
  var target_form = document.getElementById('progress_box_' + id);
  target = target_form.page_list.value;
  
   var target_page = document.getElementById('page_' + id);

  //alert(target + ':' + total_num);

  if(enable_pager == false) return;
  
  if(wait_count_plus < wait_max) {
    wait_count_plus++;
	return;
  } else {
    wait_count_plus = 0;
  }

  if(target < total_num) {
    current_page = parseInt(target) + step;

    if(current_page > total_num) current_page = total_num;

    target_form.page_list.value = current_page;
    target_page.innerHTML = current_page;

    if(current_page == total_num) {
      target_form.submit_button.value = '読み終えました！';
    } else {
      target_form.submit_button.value = current_page + 'ページまで読みました！';
    }
  }
}

function minus_page2(id, step) {
  var target_form = document.getElementById('progress_box_' + id);
  target = target_form.page_list.value;
 
   var target_page = document.getElementById('page_' + id);

  if(enable_pager == false) return;

  if(wait_count_minus < wait_max) {
    wait_count_minus++;
	return;
  } else {
    wait_count_minus = 0;
  }

  if(target > 0) {
    current_page = target - step;

    if(current_page < 1) current_page = 1;

    target_form.page_list.value = current_page;
    target_page.innerHTML = current_page;
 
    target_form.submit_button.value = current_page + 'ページまで読みました！';
  }
}

var enable_pager = true;

var wait_count_plus = 0;
var wait_count_minus = 0;

var wait_max = 5;

function toggle_pager(id) {

   var target_page = document.getElementById('page_' + id);

  if (enable_pager == true) {
    enable_pager = false;
	target_page.style.color = 'red';
  } else {
    enable_pager = true;
	target_page.style.color = 'black';
  }
}

function show_announce() {
  var target = document.getElementById('hidden_announce');
  target.style.display = 'inline';
}

function clear_add_text() {
  var target = document.getElementById('add_book_text');
  target.value = '';
  target.focus();
}

function changeColor(target) {
  target.style.backgroundColor = '#F0E68C';
}

function revertColor(target) {
  target.style.backgroundColor = '#fff';
}

// 編集ボックス表示
function change2Textbox(target, edit_box, static_rating) {
  target.style.display = 'none';
 
  var static_rating = document.getElementById(static_rating);   
  static_rating.style.display = 'none';
 
  var memo_box = document.getElementById(edit_box);
  memo_box.style.display = 'block';
}

function modMemoCancel(edit_box, display_box,  static_rating_box) {
  var memo_display_box = document.getElementById(display_box);
  memo_display_box.style.display = 'block';

  var static_rating_box = document.getElementById(static_rating_box);
  static_rating_box.style.display = 'block';
  
  var memo_box = document.getElementById(edit_box);
  memo_box.style.display = 'none';
}


var ajax_form_name = '';
var ajax_memo_name = '';
var ajax_rating_name = '';

// ajaxによるプロファイル更新
function modMemo(form_name, display_area, static_rating_box) {

  ajax_form_name = form_name;
  ajax_memo_name = display_area;
  ajax_rating_name = static_rating_box;

  //alert(ajax_form_name);

  new Ajax.Request("/mod_evaluate.php", {method: "post", parameters:Form.serialize(form_name), onComplete: function(httpObj) {
  
  //alert(ajax_rating_name);
  
  var return_str = httpObj.responseText;
  var ret_array = return_str.split('[dokusho_separator_dokusho]');

  //alert(ret_array[0]);
  //alert(ret_array[1]);

  var memo_box = document.getElementById(ajax_form_name);
  memo_box.style.display = 'none';

  var display_box = document.getElementById(ajax_memo_name);
  display_box.innerHTML = '<p class="bookdetail-label">感想</p>' + ret_array[1];
  display_box.style.display = 'block';

  var rating_box = document.getElementById(ajax_rating_name);
  rating_box.innerHTML = '<p class="bookdetail-label">評価</p>' + ret_array[0];
  rating_box.style.display = 'block';

  } });
}


function displayMemo(httpObj) {
  $("message_area").innerHTML = httpObj.responseText + "</br>";
}


// ajaxによるブックリンク
function addBookLink(id) {
  var target = 'linkage_form_' + id;
  
  // select
  var target_status_switch = 'bookshelf_status_' + id;
  
  if(document.getElementById(target_status_switch) != null) {
    target_status = document.getElementById(target_status_switch).value;
  } else {
    target_status = 0; // buy someday
  }

  new Ajax.Request("/add_booklink.php?status=" + target_status, {method: "post", parameters:Form.serialize(target), onComplete: function(httpObj) {
  
  //alert(ajax_rating_name);
  
  var return_str = httpObj.responseText;

  var linked_book_box = document.getElementById('linked_books');
  linked_book_box.innerHTML = return_str;

  var search_book_box = document.getElementById('searched_books');
  search_book_box.innerHTML = '<span style="font-size:10pt">本をブックリンクしました</span>';

  } });
}


// ajaxによるブックリンク解除
function removeBookLink(form_name) {

  new Ajax.Request("/del_booklink.php", {method: "post", parameters:Form.serialize(form_name), onComplete: function(httpObj) {
  
  //alert(ajax_rating_name);
  
  var return_str = httpObj.responseText;

  var linked_book_box = document.getElementById('linked_books');
  linked_book_box.innerHTML = return_str;

  } });
}

function removePhoto() {
  var target = document.getElementById('remove_photo');
  
  target.value = 'yes';
  
  var imagepart = document.getElementById('image_part');
  imagepart.innerHTML = '画像はありません。';
}


// ajaxによるブックリンク検索
function searchBook() {

  var searched_books = document.getElementById('searched_books');
  searched_books.innerHTML = '<img src="/img/ajax-loader.gif">';

  new Ajax.Request("/search_book_ajax.php", {method: "post", parameters:Form.serialize('linkage_search'), onComplete: function(httpObj) {
  
  //alert(ajax_rating_name);
  
  var return_str = httpObj.responseText;

  var searched_books = document.getElementById('searched_books');
  searched_books.innerHTML = return_str;

  } });
}


// ajaxによるブックリンク検索ボックスリセット
function resetBoookLinkSearchBox() {
  var search_input_box = document.getElementById('book_search_box');
  
  search_input_box.value = '';
  search_input_box.focus();
}


// ajaxによるタグ更新
function updateTag() {
  var target = 'book_tag_form';
  
  new Ajax.Request("/update_tags.php", {method: "post", parameters:Form.serialize(target), onComplete: function(httpObj) {
  
  var return_str = httpObj.responseText;

  //var tag_area = document.getElementById('tag_area');
  //tag_area.innerHTML = return_str;
  $('tag_area').innerHTML = return_str;
  $('tag_area').highlight( {duration: 0.5} );
  
  } });
}


function submitOnKeywordSearch(e){
  if (!e) var e = window.event;

  if(e.keyCode == 13) {
    searchBook();
    return false;
  }
}


function submitOnTagEdit(e){
  if (!e) var e = window.event;

  if(e.keyCode == 13) {
    updateTag();
    return false;
  }
}

function setTag(tag) {
  $('tag_input_box').value += ' ' + tag;
}


// ajaxによるブックリンク
function addBook(id, status) {
  var target = null;
  
  if(status == 'BUY_SOMEDAY') {
    target = 'buy_someday_' + id;
  } else {
    target = 'read_befor_' + id;
  }
  
  ret_target = $('listed_book_' + id);
  
  new Ajax.Request("/add_book_ajax.php", {method: "post", parameters:Form.serialize(target), onComplete: function(httpObj) {
  
  //alert(ajax_rating_name);
  
  var return_str = httpObj.responseText;

  ret_target.innerHTML = return_str;
  } });
}

function load_related_books() {
  new Ajax.Request("/search_book_by_author.php?book_search=" + search_keyword, {method: "GET", onComplete: function(httpObj) {
    var return_str = httpObj.responseText;
    $('related_books').innerHTML = return_str;
  } });
}