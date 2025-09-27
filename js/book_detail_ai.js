// ページロード時の確認

// デバッグ関数
window.debugAIModal = function() {
    const modal = document.getElementById('ai-assistant-modal');
    if (modal) {
    } else {
    }
    
    // 直接モーダルを作成してみる
    window.createAIAssistantModal();
};

// AI書評アシスタントの表示/非表示
function toggleAIAssistant() {
    try {
        const modal = document.getElementById('ai-assistant-modal');
        if (!modal) {
            createAIAssistantModal();
        } else {
            if (modal.style.display === 'none' || modal.style.display === '') {
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
            } else {
                modal.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error in toggleAIAssistant:', error);
        alert('エラーが発生しました: ' + error.message);
    }
}

// AI書評アシスタントモーダルを作成
function createAIAssistantModal() {
    try {
        const modalHTML = `
        <div id="ai-assistant-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" style="display: flex; align-items: center; justify-content: center;" onclick="if(event.target === this) closeAIAssistantModal()">
            <div class="relative mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white" style="margin-top: 2rem;" onclick="event.stopPropagation()">
                <div class="absolute top-3 right-3">
                    <button onclick="closeAIAssistantModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-robot text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">AI書評アシスタント</h3>
                            <p class="text-sm text-gray-600">AIがあなたの書評作成をお手伝いします</p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">まずは簡単な感想を入力してください</label>
                        <textarea id="ai-user-input-modal" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500"
                                  placeholder="例: すごく感動した。主人公の成長が素晴らしく、友情の大切さを再認識した。"></textarea>
                    </div>
                    
                    <div class="flex space-x-2 mb-3">
                        <button type="button" onclick="generateAIReviewModal()" 
                                class="flex-1 bg-purple-500 text-white py-2 px-4 rounded-md hover:bg-purple-600 transition-colors">
                            <i class="fas fa-magic mr-2"></i>AI書評を生成
                        </button>
                    </div>
                    
                    <!-- ローディング表示 -->
                    <div id="ai-loading-modal" class="hidden bg-white rounded-lg p-4 mb-3">
                        <div class="flex items-center">
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-purple-500 mr-3"></div>
                            <span class="text-sm text-gray-600">AI書評を生成中...</span>
                        </div>
                    </div>
                    
                    <!-- エラー表示 -->
                    <div id="ai-error-modal" class="hidden bg-red-50 border-l-4 border-red-400 p-3 mb-3">
                        <p class="text-sm text-red-700"></p>
                    </div>
                    
                    <!-- 生成結果表示 -->
                    <div id="ai-review-result-modal" class="hidden bg-white rounded-lg p-3">
                        <h5 class="font-medium text-gray-900 mb-2 text-sm">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            AIが生成した書評
                        </h5>
                        <textarea id="ai-generated-review-modal" 
                                  rows="6" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm mb-3"></textarea>
                        <div class="flex space-x-2">
                            <button type="button" onclick="useGeneratedReviewModal()" 
                                    class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 transition-colors">
                                <i class="fas fa-check mr-2"></i>この書評を使用
                            </button>
                            <button type="button" onclick="closeAIAssistantModal()" 
                                    class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 transition-colors">
                                <i class="fas fa-times mr-2"></i>キャンセル
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    } catch (error) {
        console.error('Error in createAIAssistantModal:', error);
        alert('モーダル作成エラー: ' + error.message);
    }
}

// モーダルを閉じる
function closeAIAssistantModal() {
    const modal = document.getElementById('ai-assistant-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// AI書評を生成（モーダル版）
async function generateAIReviewModal() {
    const bookTitle = document.querySelector('meta[name="book-title"]')?.content || '';
    const bookAuthor = document.querySelector('meta[name="book-author"]')?.content || '';
    const currentRating = document.querySelector('select[name="rating"]')?.value || '5';
    const userInput = document.getElementById('ai-user-input-modal')?.value;
    
    if (!userInput || !userInput.trim()) {
        const errorDiv = document.getElementById('ai-error-modal');
        if (errorDiv) {
            errorDiv.querySelector('p').textContent = '感想を入力してください';
            errorDiv.classList.remove('hidden');
        }
        return;
    }
    
    // ローディング表示
    const loadingDiv = document.getElementById('ai-loading-modal');
    const errorDiv = document.getElementById('ai-error-modal');
    const resultDiv = document.getElementById('ai-review-result-modal');
    
    if (loadingDiv) loadingDiv.classList.remove('hidden');
    if (errorDiv) errorDiv.classList.add('hidden');
    if (resultDiv) resultDiv.classList.add('hidden');
    
    try {
        const response = await fetch('/ai_review_simple.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'generate_review',
                title: bookTitle,
                author: bookAuthor,
                user_input: userInput,
                rating: parseInt(currentRating)
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const generatedReviewTextarea = document.getElementById('ai-generated-review-modal');
            if (generatedReviewTextarea) {
                generatedReviewTextarea.value = data.review;
            }
            if (resultDiv) {
                resultDiv.classList.remove('hidden');
            }
        } else {
            if (errorDiv) {
                errorDiv.querySelector('p').textContent = data.error || 'エラーが発生しました';
                errorDiv.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.error('AI Review Error:', error);
        if (errorDiv) {
            errorDiv.querySelector('p').textContent = '通信エラーが発生しました';
            errorDiv.classList.remove('hidden');
        }
    } finally {
        if (loadingDiv) loadingDiv.classList.add('hidden');
    }
}

// 生成されたレビューを使用（モーダル版）
function useGeneratedReviewModal() {
    const generatedReview = document.getElementById('ai-generated-review-modal')?.value;
    const memoTextarea = document.getElementById('memo');
    
    if (generatedReview && memoTextarea) {
        memoTextarea.value = generatedReview;
    }
    
    closeAIAssistantModal();
}

// AI書評を生成（簡易版）
async function generateAIReviewSimple() {
    const bookTitle = document.querySelector('meta[name="book-title"]')?.content || '';
    const bookAuthor = document.querySelector('meta[name="book-author"]')?.content || '';
    const currentRating = document.querySelector('select[name="rating"]')?.value || '5';
    const userInput = document.getElementById('ai-user-input')?.value;
    
    if (!userInput || !userInput.trim()) {
        const errorDiv = document.getElementById('ai-error');
        if (errorDiv) {
            errorDiv.querySelector('p').textContent = '感想を入力してください';
            errorDiv.classList.remove('hidden');
        }
        return;
    }
    
    // ローディング表示
    const loadingDiv = document.getElementById('ai-loading');
    const errorDiv = document.getElementById('ai-error');
    const resultDiv = document.getElementById('ai-review-result');
    
    if (loadingDiv) loadingDiv.classList.remove('hidden');
    if (errorDiv) errorDiv.classList.add('hidden');
    if (resultDiv) resultDiv.classList.add('hidden');
    
    try {
        const response = await fetch('/ai_review_simple.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'generate_review',
                title: bookTitle,
                author: bookAuthor,
                user_input: userInput,
                rating: parseInt(currentRating)
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const generatedReviewTextarea = document.getElementById('ai-generated-review');
            if (generatedReviewTextarea) {
                generatedReviewTextarea.value = data.review;
            }
            if (resultDiv) {
                resultDiv.classList.remove('hidden');
                // 「この書評を使用」ボタンがまだない場合は追加
                if (!resultDiv.querySelector('.use-review-button')) {
                    const buttonDiv = document.createElement('div');
                    buttonDiv.className = 'mt-2';
                    buttonDiv.innerHTML = `
                        <button type="button" onclick="useGeneratedReview()" 
                                class="use-review-button bg-green-500 text-white py-1 px-3 rounded-md hover:bg-green-600 transition-colors text-sm">
                            <i class="fas fa-check mr-1"></i>この書評を使用
                        </button>
                    `;
                    resultDiv.appendChild(buttonDiv);
                }
            }
        } else {
            if (errorDiv) {
                errorDiv.querySelector('p').textContent = data.error || 'エラーが発生しました';
                errorDiv.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.error('AI Review Error:', error);
        if (errorDiv) {
            errorDiv.querySelector('p').textContent = '通信エラーが発生しました';
            errorDiv.classList.remove('hidden');
        }
    } finally {
        if (loadingDiv) loadingDiv.classList.add('hidden');
    }
}

// 生成されたレビューを使用
function useGeneratedReview() {
    const generatedReview = document.getElementById('ai-generated-review')?.value;
    const memoTextarea = document.getElementById('memo');
    const panel = document.getElementById('ai-assistant-panel');
    
    if (generatedReview && memoTextarea) {
        memoTextarea.value = generatedReview;
    }
    if (panel) {
        panel.classList.add('hidden');
    }
}

// AIタグ生成（インライン版）
async function generateAITagsInline() {
    const reviewText = document.querySelector('meta[name="user-review"]')?.content || '';
    const bookTitle = document.querySelector('meta[name="book-title"]')?.content || '';
    const bookAuthor = document.querySelector('meta[name="book-author"]')?.content || '';
    
    if (!reviewText.trim()) {
        alert('レビューがありません。先にレビューを書いてください。');
        return;
    }
    
    // パネルを表示
    const panel = document.getElementById('ai-tags-inline-panel');
    const loading = document.getElementById('ai-tags-inline-loading');
    const result = document.getElementById('ai-tags-inline-result');
    const error = document.getElementById('ai-tags-inline-error');
    
    if (panel) panel.classList.remove('hidden');
    if (loading) loading.classList.remove('hidden');
    if (result) result.classList.add('hidden');
    if (error) error.classList.add('hidden');
    
    try {
        const response = await fetch('/ai_review_simple.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'generate_tags',
                review: reviewText,
                title: bookTitle,
                author: bookAuthor
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.tags) {
            displayGeneratedTagsInline(data.tags);
        } else {
            showTagsErrorInline(data.error || 'タグの生成に失敗しました');
        }
    } catch (error) {
        console.error('AI Tags Error:', error);
        showTagsErrorInline('通信エラーが発生しました');
    } finally {
        if (loading) loading.classList.add('hidden');
    }
}

// 生成されたタグを表示（インライン版）
function displayGeneratedTagsInline(tags) {
    const tagsList = document.getElementById('ai-tags-inline-list');
    if (!tagsList) return;
    
    tagsList.innerHTML = '';
    
    tags.forEach(tag => {
        const tagSpan = document.createElement('span');
        tagSpan.className = 'inline-block bg-green-100 text-green-800 rounded-full px-2 py-1 text-xs font-semibold cursor-pointer hover:bg-green-200 transition-colors';
        tagSpan.textContent = tag;
        tagSpan.onclick = () => addTagToInputInline(tag);
        tagsList.appendChild(tagSpan);
    });
    
    const result = document.getElementById('ai-tags-inline-result');
    if (result) result.classList.remove('hidden');
}

// タグを入力フィールドに追加（インライン版）
function addTagToInputInline(tag) {
    const tagsInput = document.querySelector('input[name="tags"][x-ref="tagsInput"]');
    if (tagsInput) {
        const currentTags = tagsInput.value.trim();
        if (currentTags) {
            // 既存のタグがある場合は、重複を避けて追加
            const tagArray = currentTags.split(',').map(t => t.trim());
            if (!tagArray.includes(tag)) {
                tagsInput.value = currentTags + ', ' + tag;
            }
        } else {
            tagsInput.value = tag;
        }
        
        // Alpine.jsのデータも更新
        tagsInput.dispatchEvent(new Event('input'));
    }
}

// タグエラー表示（インライン版）
function showTagsErrorInline(message) {
    const errorDiv = document.getElementById('ai-tags-inline-error');
    if (errorDiv) {
        const errorP = errorDiv.querySelector('p');
        if (errorP) errorP.textContent = message;
        errorDiv.classList.remove('hidden');
    }
}

// グローバルスコープに関数を登録
window.toggleAIAssistant = toggleAIAssistant;
window.createAIAssistantModal = createAIAssistantModal;
window.closeAIAssistantModal = closeAIAssistantModal;
window.generateAIReviewModal = generateAIReviewModal;
window.useGeneratedReviewModal = useGeneratedReviewModal;
