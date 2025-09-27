<?php
/**
 * AI書評アシスタントフォームコンポーネント
 * 書評投稿フォームに組み込んで使用
 */

if(!defined('CONFIG')) {
    die('Direct access not allowed');
}
?>

<!-- AI書評アシスタント -->
<div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-robot text-white"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">AI書評アシスタント</h3>
                <p class="text-sm text-gray-600">AIがあなたの書評作成をお手伝いします</p>
            </div>
        </div>
        <button type="button" 
                id="toggle-ai-assistant"
                class="text-sm text-indigo-600 hover:text-indigo-800">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>
    
    <div id="ai-assistant-content" class="space-y-4">
        <!-- ステップ1: 簡単な感想入力 -->
        <div class="bg-white rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-2">
                <i class="fas fa-edit text-indigo-500 mr-2"></i>
                まずは簡単な感想を入力してください
            </h4>
            <textarea id="ai-user-input" 
                      rows="3" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="例: すごく感動した。主人公の成長が素晴らしく、友情の大切さを再認識した。ラストシーンは涙が止まらなかった。"></textarea>
            
            <div class="mt-3 flex items-center space-x-4">
                <button type="button" 
                        onclick="aiReviewAssistant.generateReview(
                            document.getElementById('book-title')?.value || '',
                            document.getElementById('book-author')?.value || '',
                            document.getElementById('ai-user-input').value,
                            document.querySelector('input[name=rating]:checked')?.value || 3
                        )"
                        class="ai-action-button inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white text-sm font-medium rounded-md hover:from-purple-600 hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-magic mr-2"></i>
                    AI書評を生成
                </button>
                
                <div id="ai-loading" class="hidden flex items-center text-indigo-600">
                    <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    生成中...
                </div>
            </div>
        </div>
        
        <!-- エラー表示 -->
        <div id="ai-error" class="hidden bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"></p>
                </div>
            </div>
        </div>
        
        <!-- ステップ2: 生成された書評 -->
        <div id="ai-review-result" class="bg-white rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-2">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                AIが生成した書評
            </h4>
            <textarea id="ai-generated-review" 
                      rows="6" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 mb-3"
                      placeholder="AIが書評を生成すると、ここに表示されます"></textarea>
            
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        onclick="document.getElementById('memo').value = document.getElementById('ai-generated-review').value"
                        class="ai-action-button inline-flex items-center px-3 py-1 bg-green-500 text-white text-sm font-medium rounded-md hover:bg-green-600">
                    <i class="fas fa-check mr-1"></i>
                    この書評を使用
                </button>
                
                <button type="button"
                        onclick="aiReviewAssistant.improveReview(document.getElementById('ai-generated-review').value, 'もっと詳しく')"
                        class="ai-action-button inline-flex items-center px-3 py-1 bg-blue-500 text-white text-sm font-medium rounded-md hover:bg-blue-600">
                    <i class="fas fa-expand mr-1"></i>
                    もっと詳しく
                </button>
                
                <button type="button"
                        onclick="aiReviewAssistant.improveReview(document.getElementById('ai-generated-review').value, 'もっと簡潔に')"
                        class="ai-action-button inline-flex items-center px-3 py-1 bg-yellow-500 text-white text-sm font-medium rounded-md hover:bg-yellow-600">
                    <i class="fas fa-compress mr-1"></i>
                    もっと簡潔に
                </button>
                
                <button type="button"
                        onclick="aiReviewAssistant.generateTags(
                            document.getElementById('ai-generated-review').value,
                            document.getElementById('book-title')?.value || '',
                            document.getElementById('book-author')?.value || ''
                        )"
                        class="ai-action-button inline-flex items-center px-3 py-1 bg-purple-500 text-white text-sm font-medium rounded-md hover:bg-purple-600">
                    <i class="fas fa-tags mr-1"></i>
                    タグを生成
                </button>
            </div>
            
            <div id="ai-token-usage" class="hidden mt-2 text-xs text-gray-500"></div>
        </div>
        
        <!-- ステップ3: 生成されたタグ -->
        <div id="ai-tags-result" class="bg-white rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-2">
                <i class="fas fa-tags text-purple-500 mr-2"></i>
                AIが提案するタグ
            </h4>
            <div id="ai-generated-tags" class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-500">タグを生成すると、ここに表示されます</span>
            </div>
        </div>
        
        <!-- 書評プレビュー -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-2">
                <i class="fas fa-eye text-gray-500 mr-2"></i>
                プレビュー
            </h4>
            <div id="ai-review-preview" class="prose prose-sm max-w-none hidden"></div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="/template/modern/js/ai_review_assistant.js"></script>
<script>
// AI アシスタントの表示/非表示切り替え
document.getElementById('toggle-ai-assistant')?.addEventListener('click', function() {
    const content = document.getElementById('ai-assistant-content');
    const icon = this.querySelector('i');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
});
</script>