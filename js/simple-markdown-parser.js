/**
 * シンプルなMarkdownパーサー
 * AI出力のMarkdownをHTMLに変換
 */

function parseMarkdown(markdown) {
    if (!markdown) return '';
    
    // 本の推薦フォーマットを検出してボタンを追加
    // AI推薦モードの判定を追加
    if (markdown.includes('「') && markdown.includes('」') && 
        (markdown.includes('推薦理由:') || markdown.includes('チャレンジ理由:') || 
         window.currentAIMode === 'recommend' || window.currentAIMode === 'challenge')) {
        return parseBookRecommendations(markdown);
    }
    
    let html = markdown;
    
    // エスケープ処理
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // コードブロックを一時的に置換（処理から除外）
    const codeBlocks = [];
    html = html.replace(/```[\s\S]*?```/g, (match) => {
        codeBlocks.push(match);
        return `__CODE_BLOCK_${codeBlocks.length - 1}__`;
    });
    
    // インラインコードを一時的に置換
    const inlineCodes = [];
    html = html.replace(/`[^`]+`/g, (match) => {
        inlineCodes.push(match);
        return `__INLINE_CODE_${inlineCodes.length - 1}__`;
    });
    
    // 見出し
    html = html.replace(/^### (.+)$/gm, '<h3 class="text-lg font-bold mt-4 mb-2">$1</h3>');
    html = html.replace(/^## (.+)$/gm, '<h2 class="text-xl font-bold mt-6 mb-3">$1</h2>');
    html = html.replace(/^# (.+)$/gm, '<h1 class="text-2xl font-bold mt-6 mb-4">$1</h1>');
    
    // リスト（番号付き）
    html = html.replace(/^\d+\.\s+(.+)$/gm, '<li class="ml-6 mb-1 list-decimal">$1</li>');
    
    // リスト（箇条書き）- ハイフンまたはアスタリスク
    html = html.replace(/^[\-\*]\s+(.+)$/gm, '<li class="ml-6 mb-1 list-disc">$1</li>');
    
    // リストをulタグで囲む
    html = html.replace(/(<li class="[^"]*list-disc[^"]*">[\s\S]*?<\/li>\n?)+/g, (match) => {
        return '<ul class="my-3">' + match + '</ul>';
    });
    
    // リストをolタグで囲む
    html = html.replace(/(<li class="[^"]*list-decimal[^"]*">[\s\S]*?<\/li>\n?)+/g, (match) => {
        return '<ol class="my-3">' + match + '</ol>';
    });
    
    // 太字
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-bold">$1</strong>');
    html = html.replace(/__([^_]+)__/g, '<strong class="font-bold">$1</strong>');
    
    // イタリック
    html = html.replace(/\*([^*]+)\*/g, '<em class="italic">$1</em>');
    html = html.replace(/_([^_]+)_/g, '<em class="italic">$1</em>');
    
    // 引用
    html = html.replace(/^>\s+(.+)$/gm, '<blockquote class="border-l-4 border-gray-300 pl-4 my-2 italic">$1</blockquote>');
    
    // 水平線
    html = html.replace(/^---$/gm, '<hr class="my-4 border-gray-300">');
    
    // リンク
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-blue-600 hover:underline" target="_blank">$1</a>');
    
    // 段落
    html = html.split('\n\n').map(paragraph => {
        paragraph = paragraph.trim();
        if (!paragraph) return '';
        
        // すでにHTMLタグで始まっている場合は段落タグで囲まない
        if (paragraph.match(/^<[^>]+>/)) {
            return paragraph;
        }
        
        return `<p class="mb-3">${paragraph}</p>`;
    }).join('\n');
    
    // インラインコードを復元
    inlineCodes.forEach((code, index) => {
        const content = code.slice(1, -1); // バッククォートを除去
        html = html.replace(
            `__INLINE_CODE_${index}__`, 
            `<code class="bg-gray-100 px-1 py-0.5 rounded text-sm font-mono">${escapeHtml(content)}</code>`
        );
    });
    
    // コードブロックを復元
    codeBlocks.forEach((block, index) => {
        const content = block.slice(3, -3).trim(); // ```を除去
        const lines = content.split('\n');
        const language = lines[0].trim();
        const code = lines.slice(language ? 1 : 0).join('\n');
        
        html = html.replace(
            `__CODE_BLOCK_${index}__`, 
            `<pre class="bg-gray-100 p-4 rounded overflow-x-auto my-3"><code class="text-sm font-mono">${escapeHtml(code)}</code></pre>`
        );
    });
    
    return html;
}

// 本の推薦をパースしてボタン付きのHTMLを生成
function parseBookRecommendations(markdown) {
    const lines = markdown.split('\n');
    let html = '';
    let currentBook = null;
    let bookList = [];
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        
        // 本のタイトル行を検出 - 複数のパターンに対応
        let titleMatch = null;
        let title = '';
        let author = '';
        
        // パターン1: 1. **「タイトル」 / 著者**
        titleMatch = line.match(/^\d+\.\s*\*\*「(.+?)」\s*\/\s*(.+?)\*\*$/);
        if (titleMatch) {
            title = titleMatch[1];
            author = titleMatch[2];
        } else {
            // パターン2: 1. 「タイトル」 / 著者
            titleMatch = line.match(/^\d+\.\s*「(.+?)」\s*\/\s*(.+?)$/);
            if (titleMatch) {
                title = titleMatch[1];
                author = titleMatch[2];
            } else {
                // パターン3: 1. **タイトル / 著者**（「」なし）
                titleMatch = line.match(/^\d+\.\s*\*\*(.+?)\s*\/\s*(.+?)\*\*$/);
                if (titleMatch) {
                    title = titleMatch[1];
                    author = titleMatch[2];
                }
            }
        }
        
        if (titleMatch) {
            if (currentBook) {
                bookList.push(currentBook);
            }
            currentBook = {
                title: title,
                author: author,
                reason: '',
                perspective: '',
                genre: ''
            };
        } else if (currentBook) {
            // 各項目を収集
            if (line.includes('チャレンジ理由:') || line.includes('推薦理由:')) {
                const reasonMatch = line.match(/(?:チャレンジ理由|推薦理由):\s*(.+)/);
                if (reasonMatch) {
                    currentBook.reason = reasonMatch[1].trim();
                }
            } else if (line.includes('新しい視点:')) {
                const perspectiveMatch = line.match(/新しい視点:\s*(.+)/);
                if (perspectiveMatch) {
                    currentBook.perspective = perspectiveMatch[1].trim();
                }
            } else if (line.includes('ジャンル:')) {
                const genreMatch = line.match(/ジャンル:\s*(.+)/);
                if (genreMatch) {
                    currentBook.genre = genreMatch[1].trim();
                }
            }
        }
    }
    
    if (currentBook) {
        bookList.push(currentBook);
    }
    
    // HTMLを生成
    if (bookList.length > 0) {
        html = '<div class="space-y-4">';
        
        bookList.forEach((book, index) => {
            const colorTheme = window.currentAIMode === 'challenge' ? 'pink' : 'purple';
            const safeTitle = book.title.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const safeAuthor = book.author.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            
            html += `
                <div class="bg-${colorTheme}-50 rounded-lg p-4">
                    <h4 class="font-semibold text-${colorTheme}-900">${index + 1}. ${escapeHtml(book.title)}</h4>
                    <p class="text-${colorTheme}-700 text-sm">${escapeHtml(book.author)}</p>
                    
                    ${book.reason ? `<div class="mt-2 text-gray-700"><strong>理由:</strong> ${escapeHtml(book.reason)}</div>` : ''}
                    ${book.perspective ? `<div class="mt-1 text-gray-700"><strong>新しい視点:</strong> ${escapeHtml(book.perspective)}</div>` : ''}
                    
                    <div class="flex items-center justify-between mt-3">
                        ${book.genre ? `<span class="inline-block bg-${colorTheme}-200 text-${colorTheme}-800 text-xs px-2 py-1 rounded">${escapeHtml(book.genre)}</span>` : '<span></span>'}
                        <div class="space-x-2">
                            <button type="button" 
                                    onclick="searchBookToAdd('${safeTitle}', '${safeAuthor}')"
                                    class="bg-${colorTheme}-600 text-white px-3 py-1 rounded text-sm hover:bg-${colorTheme}-700">
                                <i class="fas fa-search mr-1"></i>検索して追加
                            </button>
                            <button type="button"
                                    onclick="addBookManually('${safeTitle}', '${safeAuthor}')"
                                    class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">
                                <i class="fas fa-edit mr-1"></i>手動で追加
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
    } else {
        // パースできなかった場合は通常のMarkdownパース
        return parseMarkdown(markdown.replace(/「|」/g, ''));
    }
    
    return html;
}