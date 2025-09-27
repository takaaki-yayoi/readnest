/**
 * AI書評アシスタントのJavaScript
 */

class AIReviewAssistant {
    constructor() {
        this.apiEndpoint = '/ai_review_api.php';
        this.isProcessing = false;
    }
    
    /**
     * AI書評生成
     */
    async generateReview(bookTitle, bookAuthor, userInput, rating) {
        if (this.isProcessing) return;
        
        this.isProcessing = true;
        this.showLoading();
        
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'generate_review',
                    title: bookTitle,
                    author: bookAuthor,
                    user_input: userInput,
                    rating: rating
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayGeneratedReview(data.review);
                this.showTokenUsage(data.tokens_used);
            } else {
                this.showError(data.error || 'エラーが発生しました');
            }
        } catch (error) {
            this.showError('通信エラーが発生しました');
            console.error('AI Review Error:', error);
        } finally {
            this.isProcessing = false;
            this.hideLoading();
        }
    }
    
    /**
     * 書評改善
     */
    async improveReview(review, direction) {
        if (this.isProcessing) return;
        
        this.isProcessing = true;
        this.showLoading();
        
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'improve_review',
                    review: review,
                    direction: direction
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayImprovedReview(data.improved_review);
            } else {
                this.showError(data.error || 'エラーが発生しました');
            }
        } catch (error) {
            this.showError('通信エラーが発生しました');
            console.error('AI Review Error:', error);
        } finally {
            this.isProcessing = false;
            this.hideLoading();
        }
    }
    
    /**
     * タグ生成
     */
    async generateTags(review, bookTitle, bookAuthor) {
        if (this.isProcessing) return;
        
        this.isProcessing = true;
        
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'generate_tags',
                    review: review,
                    title: bookTitle,
                    author: bookAuthor
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayGeneratedTags(data.tags);
            } else {
                this.showError(data.error || 'エラーが発生しました');
            }
        } catch (error) {
            this.showError('通信エラーが発生しました');
            console.error('AI Tag Error:', error);
        } finally {
            this.isProcessing = false;
        }
    }
    
    /**
     * 生成された書評を表示
     */
    displayGeneratedReview(review) {
        const reviewTextarea = document.getElementById('ai-generated-review');
        if (reviewTextarea) {
            reviewTextarea.value = review;
            reviewTextarea.classList.add('animate-pulse-once');
            setTimeout(() => {
                reviewTextarea.classList.remove('animate-pulse-once');
            }, 1000);
        }
        
        // プレビューも更新
        const previewDiv = document.getElementById('ai-review-preview');
        if (previewDiv) {
            previewDiv.innerHTML = this.formatReviewHtml(review);
            previewDiv.classList.remove('hidden');
        }
    }
    
    /**
     * 改善された書評を表示
     */
    displayImprovedReview(review) {
        const currentTextarea = document.getElementById('memo') || document.getElementById('ai-generated-review');
        if (currentTextarea) {
            currentTextarea.value = review;
        }
    }
    
    /**
     * 生成されたタグを表示
     */
    displayGeneratedTags(tags) {
        const tagContainer = document.getElementById('ai-generated-tags');
        if (tagContainer) {
            tagContainer.innerHTML = '';
            tags.forEach(tag => {
                const tagSpan = document.createElement('span');
                tagSpan.className = 'inline-block bg-book-primary-100 text-book-primary-800 rounded-full px-3 py-1 text-sm font-semibold mr-2 mb-2 cursor-pointer hover:bg-book-primary-200';
                tagSpan.textContent = tag;
                tagSpan.onclick = () => this.addTagToInput(tag);
                tagContainer.appendChild(tagSpan);
            });
        }
    }
    
    /**
     * タグを入力フィールドに追加
     */
    addTagToInput(tag) {
        const tagInput = document.getElementById('tags') || document.querySelector('input[name="tags"]');
        if (tagInput) {
            const currentTags = tagInput.value.trim();
            if (currentTags) {
                tagInput.value = currentTags + ', ' + tag;
            } else {
                tagInput.value = tag;
            }
        }
    }
    
    /**
     * 書評をHTMLフォーマット
     */
    formatReviewHtml(review) {
        return review
            .split('\n\n')
            .map(paragraph => `<p class="mb-4">${this.escapeHtml(paragraph)}</p>`)
            .join('');
    }
    
    /**
     * HTMLエスケープ
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * ローディング表示
     */
    showLoading() {
        const loadingDiv = document.getElementById('ai-loading');
        if (loadingDiv) {
            loadingDiv.classList.remove('hidden');
        }
        
        // ボタンを無効化
        const buttons = document.querySelectorAll('.ai-action-button');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        });
    }
    
    /**
     * ローディング非表示
     */
    hideLoading() {
        const loadingDiv = document.getElementById('ai-loading');
        if (loadingDiv) {
            loadingDiv.classList.add('hidden');
        }
        
        // ボタンを有効化
        const buttons = document.querySelectorAll('.ai-action-button');
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        });
    }
    
    /**
     * エラー表示
     */
    showError(message) {
        const errorDiv = document.getElementById('ai-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
            setTimeout(() => {
                errorDiv.classList.add('hidden');
            }, 5000);
        } else {
            alert('エラー: ' + message);
        }
    }
    
    /**
     * トークン使用量表示
     */
    showTokenUsage(tokens) {
        const tokenDiv = document.getElementById('ai-token-usage');
        if (tokenDiv) {
            tokenDiv.textContent = `使用トークン数: ${tokens}`;
            tokenDiv.classList.remove('hidden');
        }
    }
}

// グローバルインスタンス
const aiReviewAssistant = new AIReviewAssistant();

// アニメーション用CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse-once {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .animate-pulse-once {
        animation: pulse-once 1s ease-in-out;
    }
`;
document.head.appendChild(style);