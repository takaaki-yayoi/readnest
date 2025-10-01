/**
 * いいね機能のJavaScript
 */

(function() {
    'use strict';

    // CSRFトークンを取得
    function getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            const token = metaTag.getAttribute('content');
            console.log('CSRF token found in meta tag:', token);
            return token;
        }

        // フォームから取得を試みる
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        if (tokenInput) {
            const token = tokenInput.value;
            console.log('CSRF token found in form input:', token);
            return token;
        }

        console.error('CSRF token not found!');
        alert('CSRFトークンが見つかりません。ページを再読み込みしてください。');
        return '';
    }

    // いいねボタンのクリックイベント
    function handleLikeButtonClick(event) {
        event.preventDefault();

        // イベント委譲の場合はthisを使用、直接の場合はcurrentTargetを使用
        const button = this instanceof HTMLElement ? this : event.currentTarget;

        if (!button || !button.classList) {
            console.error('Invalid button element:', button);
            return;
        }

        // ボタンが無効化されている場合は何もしない
        if (button.classList.contains('disabled')) {
            return;
        }

        // データ属性から情報を取得
        const targetType = button.dataset.targetType;
        const targetId = button.dataset.targetId;
        const reviewUserId = button.dataset.reviewUserId; // レビューの場合のみ

        if (!targetType || !targetId) {
            console.error('Target type or ID is missing');
            return;
        }

        // ボタンを無効化（連打防止）
        button.classList.add('disabled');
        button.style.opacity = '0.5';
        button.style.pointerEvents = 'none';

        // CSRFトークンを取得
        const csrfToken = getCSRFToken();
        if (!csrfToken) {
            // トークンが取得できない場合は処理を中断
            button.classList.remove('disabled');
            button.style.opacity = '';
            button.style.pointerEvents = '';
            return;
        }

        // リクエストデータ
        const requestData = {
            csrf_token: csrfToken,
            target_type: targetType,
            target_id: parseInt(targetId),
            action: 'toggle'
        };

        // レビューの場合はreview_user_idも送信
        if (targetType === 'review' && reviewUserId) {
            requestData.review_user_id = parseInt(reviewUserId);
        }

        console.log('Sending like request:', requestData);

        // APIリクエスト
        fetch('/api/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Like API response:', data);
            if (data.success) {
                // UIを更新
                console.log('Updating UI with:', data.data);
                updateLikeButton(button, data.data.is_liked, data.data.like_count);
            } else {
                console.error('Like API error:', data.message);
                alert(data.message || 'いいねの処理に失敗しました');
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            alert('通信エラーが発生しました');
        })
        .finally(() => {
            // ボタンを再度有効化
            button.classList.remove('disabled');
            button.style.opacity = '';
            button.style.pointerEvents = '';
        });
    }

    // いいねボタンのUIを更新
    function updateLikeButton(button, isLiked, likeCount) {
        const icon = button.querySelector('.like-icon');
        const countSpan = button.querySelector('.like-count');

        if (icon) {
            if (isLiked) {
                icon.classList.remove('far', 'text-gray-400');
                icon.classList.add('fas', 'text-red-500');
            } else {
                icon.classList.remove('fas', 'text-red-500');
                icon.classList.add('far', 'text-gray-400');
            }
        }

        if (countSpan) {
            countSpan.textContent = likeCount.toLocaleString();
        }

        // アニメーション効果
        button.classList.add('like-animation');
        setTimeout(() => {
            button.classList.remove('like-animation');
        }, 300);
    }

    // DOMが読み込まれた後に実行
    function init() {
        console.log('Like.js initialized');

        // すべてのいいねボタンにイベントリスナーを設定
        const likeButtons = document.querySelectorAll('.like-button');
        console.log('Found ' + likeButtons.length + ' like buttons');

        likeButtons.forEach(button => {
            button.addEventListener('click', handleLikeButtonClick);
            console.log('Added listener to button:', button);
        });
    }

    // ページ読み込み時に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // 動的に追加されたボタンにも対応（イベント委譲）
    document.addEventListener('click', function(event) {
        const button = event.target.closest('.like-button');
        if (button) {
            console.log('Like button clicked via delegation');
            handleLikeButtonClick.call(button, event);
        }
    });

    // CSSアニメーション
    const style = document.createElement('style');
    style.textContent = `
        .like-animation {
            animation: like-pulse 0.3s ease-in-out;
        }

        @keyframes like-pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
        }

        .like-button {
            transition: background-color 0.2s, transform 0.1s;
        }

        .like-button:active {
            transform: scale(0.95);
        }

        .like-button.disabled {
            cursor: not-allowed;
        }
    `;
    document.head.appendChild(style);

})();