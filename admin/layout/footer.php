<?php
/**
 * 管理画面フッター
 * PHP 8.2.28対応版
 */
?>
            </div>
        </main>
    </div>
    
    <script>
        // グローバルなJavaScript設定
        const confirmDelete = (message = '本当に削除しますか？') => {
            return confirm(message);
        };
        
        // フラッシュメッセージの自動非表示
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-auto-hide');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>