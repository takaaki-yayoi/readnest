<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// 追加のヘッド要素
ob_start();
?>
<!-- D3.js for visualization -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<style>
.node {
  cursor: pointer;
}

.node:hover {
  stroke: #000;
  stroke-width: 1.5px;
}

.node-label {
  font-size: 12px;
  pointer-events: none;
}

/* モバイル用ラベル調整 */
@media (max-width: 768px) {
  .node-label {
    font-size: 10px;
  }
}

.tooltip {
  position: absolute;
  text-align: left;
  padding: 10px;
  font-size: 12px;
  background: rgba(0, 0, 0, 0.9);
  color: white;
  border-radius: 8px;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.3s;
  z-index: 1000;
  max-width: 280px;
}

/* モバイル用ツールチップ調整 */
@media (max-width: 768px) {
  .tooltip {
    font-size: 11px;
    padding: 8px;
    max-width: 200px;
  }
}

.legend {
  font-size: 14px;
}

.ai-suggestion {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 2rem;
}

.genre-card {
  transition: transform 0.2s;
}

.genre-card:hover {
  transform: translateY(-2px);
}

/* ズームコントロールのスタイル */
.zoom-controls {
  display: flex !important;
  visibility: visible !important;
}

.zoom-controls button {
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  cursor: pointer;
}

/* モバイル用ズームコントロール */
@media (max-width: 768px) {
  .zoom-controls button span {
    display: none;
  }
  
  .zoom-controls button i {
    margin-right: 0 !important;
  }
  
  .zoom-controls {
    gap: 0.25rem !important;
  }
  
  .zoom-controls button {
    padding: 0.5rem !important;
  }
}

/* ローディングアニメーション */
.loading-spinner {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid #ffffff;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
}

/* メインローディング用（グレー系） */
#loading .loading-spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #FF6B6B;
}

/* AI提案ローディング用（白系） */
#ai-suggestions .loading-spinner {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid #ffffff;
  width: 40px;
  height: 40px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* ビジュアライゼーション高さ調整 */
#visualization {
  overflow: hidden;
  position: relative;
}

#visualization svg {
  display: block;
  max-width: 100%;
  height: auto;
}

@media (max-width: 768px) {
  #visualization {
    height: 400px !important;
  }
}

@media (orientation: landscape) and (max-height: 500px) {
  #visualization {
    height: 300px !important;
  }
}

/* ビジュアライゼーションコンテナのz-index設定 */
#visualization {
  z-index: 10;
  position: relative;
}

/* SVG要素のオーバーフロー防止 */
#visualization svg {
  overflow: visible;
  position: relative;
  z-index: 10;
}</style>
<?php
$d_additional_head = ob_get_clean();

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- ヘッダー - レスポンシブ対応 -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6 sm:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-map-marked-alt text-readnest-primary mr-2 sm:mr-3 text-base sm:text-xl"></i>
                        <span class="line-clamp-1">
                            <?php echo $is_my_map ? 'あなたの' : html($display_nickname) . 'さんの'; ?>読書マップ
                        </span>
                        <a href="/help.php#reading-map" target="_blank" 
                           class="ml-2 text-sm text-blue-500 hover:text-blue-600"
                           title="読書マップの使い方">
                            <i class="fas fa-question-circle"></i>
                        </a>
                    </h1>
                    <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600">
                        読んだ本を著者やタグで分類し、読書傾向を視覚的に把握できます
                    </p>
                    <p class="mt-1 text-xs sm:text-sm text-gray-500">
                        💡 <span class="hidden xs:inline">バブルやタグを</span>クリックで<span class="hidden xs:inline">その著者やタグの本を</span>検索
                    </p>
                </div>
                <div class="flex gap-2 sm:gap-3 self-start sm:self-center">
                    <?php if ($is_my_map): ?>
                    <a href="/bookshelf.php" class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-readnest-primary text-white rounded-lg hover:bg-readnest-primary-dark transition-colors text-sm sm:text-base">
                        <i class="fas fa-book mr-1.5 sm:mr-2 text-sm"></i>
                        <span class="hidden xs:inline">本棚を</span>見る
                    </a>
                    <?php endif; ?>
                    <a href="/book_search.php" class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm sm:text-base">
                        <i class="fas fa-search mr-1.5 sm:mr-2 text-sm"></i>
                        <span class="hidden xs:inline">本を</span>探す
                    </a>
                </div>
            </div>
        </div>

        <!-- 統計情報 - レスポンシブ対応 -->
        <div class="grid grid-cols-2 sm:grid-cols-2 tablet:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm text-gray-600">総冊数</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900" id="stat-total">-</p>
                    </div>
                    <i class="fas fa-book text-2xl sm:text-3xl text-gray-400"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm text-gray-600">読了冊数</p>
                        <p class="text-xl sm:text-2xl font-bold text-green-600" id="stat-finished">-</p>
                    </div>
                    <i class="fas fa-check-circle text-2xl sm:text-3xl text-green-400"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm text-gray-600">読書中</p>
                        <p class="text-xl sm:text-2xl font-bold text-blue-600" id="stat-reading">-</p>
                    </div>
                    <i class="fas fa-book-open text-2xl sm:text-3xl text-blue-400"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm text-gray-600">探索ジャンル</p>
                        <p class="text-xl sm:text-2xl font-bold text-purple-600" id="stat-genres">-</p>
                    </div>
                    <i class="fas fa-compass text-2xl sm:text-3xl text-purple-400"></i>
                </div>
            </div>
        </div>
        
        <!-- パフォーマンス最適化通知 -->
        <div id="performance-notice" class="hidden mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                <div class="text-sm text-blue-700">
                    <strong>パフォーマンス最適化:</strong> 大量のデータのため、一部のみ表示しています。
                    <span id="performance-details"></span>
                </div>
            </div>
        </div>

        <!-- ビジュアライゼーション - レスポンシブ対応 -->
        <div class="bg-white rounded-lg shadow-lg p-3 sm:p-4 md:p-6 mb-6 sm:mb-8 overflow-hidden">
            <div id="visualization" style="width: 100%; position: relative; overflow: hidden;" class="h-[400px] sm:h-[500px] md:h-[600px] landscape:h-[300px]">
                <!-- ローディング表示 - プログレスバー付き -->
                <div id="loading" class="flex items-center justify-center h-full">
                    <div class="text-center w-full max-w-md px-4">
                        <div class="loading-spinner mx-auto mb-4"></div>
                        <p class="text-gray-600 text-base sm:text-lg font-medium mb-3">読書マップを生成中...</p>
                        <div id="loading-progress" class="mb-2">
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                <div class="bg-readnest-primary h-2 rounded-full" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-600">初期化中...</p>
                        </div>
                        <p class="text-gray-500 text-xs sm:text-sm mt-2">初回読み込みは時間がかかる場合があります</p>
                    </div>
                </div>
                
                <!-- ズーム情報表示 -->
                <div id="zoom-info" class="absolute top-2 right-2 bg-black bg-opacity-70 text-white px-2 py-1 rounded text-xs hidden">
                    <span id="zoom-level">100%</span>
                </div>
            </div>
            <div class="tooltip"></div>
            
            <!-- ズームコントロール（マップの下に配置） - レスポンシブ対応 -->
            <div class="mt-3 sm:mt-4 flex justify-center">
                <div class="zoom-controls flex gap-2 bg-gray-100 rounded-lg p-1.5 sm:p-2">
                    <button id="zoom-in" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-white text-gray-700 rounded hover:bg-blue-50 hover:text-blue-600 transition-colors flex items-center shadow-sm text-sm" title="ズームイン">
                        <i class="fas fa-search-plus sm:mr-2"></i>
                        <span class="text-sm hidden sm:inline">拡大</span>
                    </button>
                    <button id="zoom-out" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-white text-gray-700 rounded hover:bg-blue-50 hover:text-blue-600 transition-colors flex items-center shadow-sm text-sm" title="ズームアウト">
                        <i class="fas fa-search-minus sm:mr-2"></i>
                        <span class="text-sm hidden sm:inline">縮小</span>
                    </button>
                    <button id="reset-zoom" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-white text-gray-700 rounded hover:bg-blue-50 hover:text-blue-600 transition-colors flex items-center shadow-sm text-sm" title="ズームリセット">
                        <i class="fas fa-expand-arrows-alt sm:mr-2"></i>
                        <span class="text-sm hidden sm:inline">リセット</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- AI提案セクション - レスポンシブ対応 -->
        <div class="ai-suggestion p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl md:text-2xl font-bold mb-3 sm:mb-4 flex items-center">
                <i class="fas fa-robot mr-2 sm:mr-3 text-base sm:text-lg"></i>
                次の読書の冒険へ
            </h2>
            <div id="ai-suggestions" class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                <!-- AIの提案がここに表示される -->
            </div>
        </div>

        <!-- ジャンル詳細 -->
        <div class="mt-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">ジャンル別詳細</h2>
                <?php if ($is_my_map): ?>
                <a href="/bookshelf.php" class="inline-flex items-center px-3 py-2 text-sm bg-readnest-primary text-white rounded-lg hover:bg-readnest-primary-dark transition-colors">
                    <i class="fas fa-list mr-2"></i>
                    本棚で詳細を見る
                </a>
                <?php endif; ?>
            </div>
            <div id="genre-details" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- ジャンル詳細カードがここに表示される -->
            </div>
        </div>
    </div>
</div>

<script>
// ユーザーIDを設定
const userId = '<?php echo html($user_id); ?>';
let currentView = 'bubble';
let mapData = null;

// プログレッシブローディングの実装
const loadingProgress = document.getElementById('loading-progress');
let progressPercent = 0;

// プログレスバーの更新
function updateProgress(percent, message) {
    progressPercent = Math.min(percent, 100);
    if (loadingProgress) {
        loadingProgress.innerHTML = `
            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                <div class="bg-readnest-primary h-2 rounded-full transition-all duration-300" style="width: ${progressPercent}%"></div>
            </div>
            <p class="text-xs text-gray-600">${message}</p>
        `;
    }
}

// 初期表示
updateProgress(10, 'データを取得中...');

// データを取得（v2画像付き版を使用）
fetch(`/api/reading_map_data_v2.php?user=${userId}`, {
    credentials: 'same-origin',
    headers: {
        'Accept': 'application/json'
    }
})
    .then(response => {
        updateProgress(30, 'データを解析中...');
        return response.text();
    })
    .then(text => {
        updateProgress(50, 'マップを生成中...');
        try {
            const data = JSON.parse(text);
            
            if (data.error) {
                const loadingEl = document.getElementById('loading');
                if (loadingEl) loadingEl.style.display = 'none';
                document.getElementById('visualization').innerHTML = '<div class="text-red-600 text-center p-4">エラー: ' + data.error + '</div>';
                return;
            }
            
            // ローディングを非表示
            const loadingEl = document.getElementById('loading');
            if (loadingEl) loadingEl.style.display = 'none';
            
            // データがある場合のみ処理
            if (data.data && data.data.children && data.data.children.length > 0) {
                // デバッグ：タグカテゴリの確認
                data.data.children.forEach(category => {
                    if (category.name === 'よく使うタグ') {
                        if (category.children) {
                            category.children.slice(0, 3).forEach(tag => {
                            });
                        }
                    }
                });
                
                mapData = data;
                updateProgress(70, 'ビジュアライゼーションを描画中...');
                updateStats(data.stats);
                
                // 非同期でチャートを描画
                setTimeout(() => {
                    drawBubbleChart(data.data);
                    updateProgress(90, '最終調整中...');
                    
                    // 他の要素を遅延読み込み
                    setTimeout(() => {
                        const loadingEl = document.getElementById('loading');
                if (loadingEl) loadingEl.style.display = 'none';
                        updateProgress(100, '完了');
                        generateAISuggestions(data.stats);
                        displayGenreDetails(data.data);
                        
                        // 画像を遅延読み込み（チャートが描画された後）
                        setTimeout(() => {
                            // ノードクラスを確認
                            loadImagesLazily();
                        }, 200);
                    }, 100);
                }, 50);
            } else {
                document.getElementById('visualization').innerHTML = '<div class="text-gray-600 text-center p-8"><i class="fas fa-book-open text-4xl mb-4"></i><p>まだ読書データがありません。<br>本を追加して読書を始めましょう！</p></div>';
            }
        } catch (e) {
            const loadingEl = document.getElementById('loading');
            if (loadingEl) loadingEl.style.display = 'none';
            document.getElementById('visualization').innerHTML = '<div class="text-red-600 text-center p-4">データの解析に失敗しました: ' + e.message + '</div>';
        }
    })
    .catch(error => {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('visualization').innerHTML = '<div class="text-red-600 text-center p-4">データの取得に失敗しました: ' + error.message + '</div>';
    });

// 統計情報を更新
function updateStats(stats) {
    document.getElementById('stat-total').textContent = stats.total_books;
    document.getElementById('stat-finished').textContent = stats.finished_books;
    document.getElementById('stat-reading').textContent = stats.reading_books;
    document.getElementById('stat-genres').textContent = stats.genres_explored;
    
    // パフォーマンス最適化通知を表示
    if (stats.performance_optimized) {
        const notice = document.getElementById('performance-notice');
        const details = document.getElementById('performance-details');
        let message = '';
        
        if (stats.total_authors > stats.displayed_authors) {
            message += `著者: ${stats.displayed_authors}/${stats.total_authors}件を表示`;
        }
        if (stats.total_tags > stats.displayed_tags) {
            if (message) message += ', ';
            message += `タグ: ${stats.displayed_tags}/${stats.total_tags}件を表示`;
        }
        
        details.textContent = message;
        notice.classList.remove('hidden');
    }
    
}

// バブルチャートを描画
function drawBubbleChart(data) {
    const container = document.getElementById('visualization');
    container.innerHTML = '';
    
    const width = container.offsetWidth;
    // モバイルデバイスでの高さ調整
    const isMobile = window.innerWidth <= 768;
    const isLandscape = window.innerHeight < 500 && window.innerWidth > window.innerHeight;
    const height = isLandscape ? 300 : (isMobile ? 400 : 600);
    
    const svg = d3.select('#visualization')
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .attr('viewBox', `0 0 ${width} ${height}`)
        .attr('preserveAspectRatio', 'xMidYMid meet')
        .style('max-width', '100%')
        .style('height', 'auto');
    
    // ズーム機能を追加（倍率を大幅に拡張）
    const zoom = d3.zoom()
        .scaleExtent([0.1, 50])  // 最大50倍まで拡大可能
        .on('zoom', function(event) {
            g.attr('transform', event.transform);
            updateZoomInfo(event.transform.k);
        });
    
    svg.call(zoom);
    
    // グローバルズーム変数に保存（リセット用）
    window.currentZoom = zoom;
    window.currentSvg = svg;
    
    // 初期ズーム情報を表示
    updateZoomInfo(1);
    
    // パックレイアウトを作成（パディングを追加して枠内に収める）
    const padding = 20;
    
    // メインのグループ要素を作成（中央に配置）
    const g = svg.append('g')
        .attr('transform', `translate(${padding}, ${padding})`);
    
    const pack = d3.pack()
        .size([width - padding * 2, height - padding * 2])
        .padding(3);
    
    // 階層データを作成
    const root = d3.hierarchy(data)
        .sum(d => d.value)
        .sort((a, b) => b.value - a.value);
    
    // ノードを計算
    const nodes = pack(root).descendants();
    
    // パフォーマンス最適化: ノード数制限
    const maxNodes = 1000;
    const displayNodes = nodes.length > maxNodes ? nodes.slice(0, maxNodes) : nodes;
    
    // データの境界を計算（リセット用）
    if (displayNodes.length > 0) {
        const xExtent = d3.extent(displayNodes, d => d.x);
        const yExtent = d3.extent(displayNodes, d => d.y);
        const xRange = xExtent[1] - xExtent[0];
        const yRange = yExtent[1] - yExtent[0];
        
        // 画面に収まるスケールを計算
        const xScale = width / (xRange + 100); // 余白を追加
        const yScale = height / (yRange + 100);
        const scale = Math.min(xScale, yScale, 1); // 最大1倍
        
        // 中心位置を計算
        const xCenter = (xExtent[0] + xExtent[1]) / 2;
        const yCenter = (yExtent[0] + yExtent[1]) / 2;
        
        // リセット用の変換を保存
        window.resetTransform = d3.zoomIdentity
            .translate(width / 2, height / 2)
            .scale(scale)
            .translate(-xCenter, -yCenter);
            
        // 初期表示時も画面全体に収まるように設定
        svg.call(zoom.transform, window.resetTransform);
    }
    
    // カラースケール
    const color = d3.scaleOrdinal(d3.schemeCategory10);
    
    // tooltip
    const tooltip = d3.select('.tooltip');
    
    // ノードを描画（制限されたデータを使用）
    const node = g.selectAll('.node')
        .data(displayNodes)
        .enter().append('g')
        .attr('class', d => {
            let className = 'node';
            if (d.depth === 1) className += ' node-category';
            if (d.depth === 2) {
                className += ' node-leaf';
                if (d.parent && d.parent.data.name === 'よく使うタグ') {
                    className += ' node-tag';
                } else {
                    className += ' node-author';
                }
            }
            return className;
        })
        .each(function(d) {
            // 遅延読み込み用にデータ属性を設定
            if (d.depth === 2) {
                if (d.parent && d.parent.data.name === 'よく使うタグ') {
                    if (!d.data.tag_name) {
                        d.data.tag_name = d.data.name.replace(/ \(\d+冊\)$/, '');
                    }
                } else {
                    if (!d.data.author_name) {
                        d.data.author_name = d.data.name.replace(/ \(\d+冊\)$/, '');
                    }
                }
            }
        })
        .attr('transform', d => `translate(${d.x},${d.y})`)
        .style('cursor', d => d.depth === 2 ? 'pointer' : 'default')
        .on('click', function(event, d) {
            if (d.depth === 2) {
                handleNodeClick(d);
            }
        })
        .style('pointer-events', d => d.depth === 2 ? 'all' : 'none');  // depth 2のみクリック可能に
    
    // 円を追加
    const circles = node.append('circle')
        .attr('r', d => d.r)
        .style('fill', d => {
            if (d.depth === 0) return '#fff';
            if (d.depth === 1) return d.data.color || color(d.data.name);
            return d.parent.data.color || color(d.parent.data.name);
        })
        .style('fill-opacity', d => d.depth === 0 ? 0 : d.depth === 1 ? 0.6 : 0.3)
        .style('stroke', d => d.depth === 1 ? 'transparent' : '#999')  // depth 1 の縁を透明に
        .style('stroke-width', d => d.depth === 0 ? 0 : 1)
        .style('cursor', d => d.depth === 2 ? 'pointer' : 'default');  // depth 2のみポインターカーソル
    
    // 本の表紙画像を追加（v2 APIは画像データを含む）
    node.filter(d => d.depth === 2 && d.data.images && d.data.images.length > 0)
        .each(function(d) {
            const nodeElement = d3.select(this);
            const images = d.data.images;
            const radius = d.r;
            
            
            // 複数画像の場合の配置計算
            if (radius < 15) {
                // 非常に小さいノードは1枚だけ表示
                addBookImage(nodeElement, images[0], 0, 0, Math.min(radius * 1.8, 25));
            } else if (images.length === 1) {
                // 1冊の場合：円の直径の90%を使用
                addBookImage(nodeElement, images[0], 0, 0, Math.min(radius * 1.9, 100));
            } else if (images.length === 2) {
                // 2冊の場合：左右に配置
                const imageSize = Math.min(radius * 1.4, 65);
                const offset = imageSize * 0.52;
                addBookImage(nodeElement, images[0], -offset, 0, imageSize);
                addBookImage(nodeElement, images[1], offset, 0, imageSize);
            } else if (images.length >= 3) {
                // 3冊以上の場合：三角形配置
                const imageSize = Math.min(radius * 1.1, 50);
                const offset = imageSize * 0.6;
                addBookImage(nodeElement, images[0], 0, -offset, imageSize);
                addBookImage(nodeElement, images[1], -offset, offset, imageSize);
                addBookImage(nodeElement, images[2], offset, offset, imageSize);
            }
        });
    
    // イベントハンドラーを円に追加
    circles
        .on('mouseover', function(event, d) {
            if (d.depth !== 2) return;  // depth 2のみホバー効果
            
            // ホバー時のエフェクト
            d3.select(this)
                .style('stroke-width', 2)
                .style('stroke', '#333');
            
            tooltip.transition()
                .duration(200)
                .style('opacity', 1);
            
            let content = `
                <strong>${d.data.name}</strong><br/>
                総冊数: ${d.data.value || 0}<br/>
                読了: ${d.data.finished || 0}<br/>
                読書中: ${d.data.reading || 0}<br/>
                未読: ${d.data.unread || 0}<br/>
            `;
            
            // 画像がある場合は本のタイトルも表示
            if (d.data.images && d.data.images.length > 0) {
                content += '<br/><em style="color: #666; font-size: 10px;">代表作:</em><br/>';
                d.data.images.slice(0, 2).forEach(img => {
                    content += `<span style="color: #666; font-size: 10px;">• ${img.title}</span><br/>`;
                });
            }
            
            // 検索タイプに応じたメッセージ
            if (d.parent && d.parent.data.name === 'よく使うタグ') {
                content += '<em style="color: #888; font-size: 11px;">クリックしてタグで本棚を検索</em>';
            } else if (d.depth === 2) {
                content += '<em style="color: #888; font-size: 11px;">クリックして著者で本棚を検索</em>';
            }
            
            tooltip.html(content)
                .style('left', (event.pageX + 10) + 'px')
                .style('top', (event.pageY - 28) + 'px');
        })
        .on('mouseout', function(event, d) {
            // ホバー解除時のエフェクト
            d3.select(this)
                .style('stroke-width', d.depth === 0 ? 0 : 1)
                .style('stroke', d.depth === 1 ? 'transparent' : '#999');
            
            tooltip.transition()
                .duration(500)
                .style('opacity', 0);
        });
    
    // ラベルを追加
    node.append('text')
        .attr('class', 'node-label')
        .attr('dy', d => d.depth === 1 ? `-${d.r + 10}px` : '.3em')  // depth 1 は円の上に配置
        .style('text-anchor', 'middle')
        .style('font-size', d => {
            if (d.depth === 1) return '14px';  // カテゴリは固定サイズ
            return Math.min(d.r / 3, 16) + 'px';
        })
        .style('font-weight', d => d.depth === 1 ? 'bold' : 'normal')  // カテゴリは太字
        .style('fill', d => d.depth === 1 ? '#444' : '#333')
        .style('pointer-events', 'none') // テキストはクリックイベントを無視
        .text(d => d.depth !== 0 && (d.depth === 1 || d.r > 20) ? d.data.name : '');
}

// 本の画像を追加するヘルパー関数
function addBookImage(nodeElement, imageData, x, y, size) {
    const imageGroup = nodeElement.append('g')
        .attr('transform', `translate(${x}, ${y})`);
    
    // 画像のクリッピングパスを作成
    const clipId = `clip-${Math.random().toString(36).substr(2, 9)}`;
    const defs = nodeElement.select('defs').empty() ? nodeElement.append('defs') : nodeElement.select('defs');
    
    defs.append('clipPath')
        .attr('id', clipId)
        .append('circle')
        .attr('r', size / 2);
    
    // Amazon画像URLをHTTPSに変換
    let imageUrl = imageData.url;
    if (imageUrl && imageUrl.includes('ecx.images-amazon.com')) {
        imageUrl = imageUrl.replace('http://', 'https://');
    }
    
    // 画像を追加
    const imgElement = imageGroup.append('image')
        .attr('href', imageUrl)
        .attr('x', -size / 2)
        .attr('y', -size / 2)
        .attr('width', size)
        .attr('height', size)
        .attr('clip-path', `url(#${clipId})`)
        .style('cursor', 'pointer')
        .style('pointer-events', 'none')  // 画像はクリックイベントを無視
        .on('error', function() {
            // 画像読み込みエラー時はno-image-book.pngに差し替え
            d3.select(this).attr('href', '/img/no-image-book.png');
        })
        ;
    
    // 画像の境界線（1pxの白い縁）
    imageGroup.append('circle')
        .attr('r', size / 2)
        .style('fill', 'none')
        .style('stroke', '#fff')
        .style('stroke-width', 1)
        .style('pointer-events', 'none');
}

// ノードクリック時の処理
function handleNodeClick(d) {
    
    // クリックされたノードの名前を取得
    let searchQuery = d.data.name;
    
    // 括弧内の数字を削除（例: "森沢洋介 (37冊)" → "森沢洋介"）
    searchQuery = searchQuery.replace(/\s*\([^)]*\)$/, '');
    
    // カテゴリノード（depth=1）の場合は処理しない
    if (d.depth === 1) {
        return;
    }
    
    // depth=2のノードのみ処理
    if (d.depth !== 2) {
        return;
    }
    
    // 検索タイプを判定
    let searchType = 'author'; // デフォルトは著者検索
    
    // デバッグ：親ノード情報
    
    // タグカテゴリの子要素の場合はタグ検索
    if (d.parent && d.parent.data.name === 'よく使うタグ') {
        searchType = 'tag';
    }
    
    // 確認ダイアログなしで直接検索ページへ遷移
    if (searchType === 'tag') {
        // タグ検索ページへ（本棚のタグ検索）
        const url = `/bookshelf.php?search_type=tag&search_word=${encodeURIComponent(searchQuery)}`;
        window.location.href = url;
    } else {
        // 著者検索ページへ（本棚検索）
        const url = `/bookshelf.php?search_type=author&search_word=${encodeURIComponent(searchQuery)}`;
        window.location.href = url;
    }
}

// AI提案を生成
function generateAISuggestions(stats) {
    const container = document.getElementById('ai-suggestions');
    
    // ローディング表示
    container.innerHTML = `
        <div class="flex items-center justify-center p-8">
            <div class="loading-spinner mr-4"></div>
            <span class="text-white text-lg">AI が読書履歴を分析しています...</span>
        </div>
    `;
    
    // AIから提案を取得
    fetch(`/api/reading_suggestions.php?user=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                // フォールバック提案を使用
                displaySuggestions(data.fallback_suggestions || []);
                return;
            }
            
            displaySuggestions(data.suggestions);
        })
        .catch(error => {
            // フォールバック提案
            displaySuggestions([
                {
                    type: 'genre_exploration',
                    title: '新しい本を探してみませんか？',
                    description: '読書の幅を広げて新たな発見をしましょう。',
                    action_text: 'おすすめ本を探す',
                    action_url: '/add_book.php'
                }
            ]);
        });
}

// 提案を表示
function displaySuggestions(suggestions) {
    const container = document.getElementById('ai-suggestions');
    
    if (!suggestions || suggestions.length === 0) {
        container.innerHTML = `
            <div class="bg-white bg-opacity-20 rounded-lg p-4 text-center">
                <i class="fas fa-book text-3xl mb-2"></i>
                <p class="text-sm opacity-90">読書データを蓄積中です。もう少し本を読んでからお試しください。</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = suggestions.map(suggestion => {
        const iconMap = {
            'genre_exploration': 'fa-compass',
            'author_deep_dive': 'fa-user-friends',
            'reading_pace': 'fa-clock',
            'unread_focus': 'fa-bookmark'
        };
        
        const icon = iconMap[suggestion.type] || 'fa-lightbulb';
        
        return `
            <div class="bg-white bg-opacity-20 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas ${icon} text-2xl mr-4 mt-1"></i>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg mb-2">${suggestion.title}</h3>
                        <p class="text-sm mb-3 opacity-90">${suggestion.description}</p>
                        <button onclick="handleAISuggestionClick('${suggestion.action_url}')" 
                                class="px-4 py-2 bg-white text-purple-700 rounded-lg hover:bg-gray-100 transition-colors text-sm font-medium">
                            ${suggestion.action_text}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// AI提案ボタンクリック時の処理
function handleAISuggestionClick(actionUrl) {
    if (actionUrl && actionUrl.startsWith('/')) {
        window.location.href = actionUrl;
    } else {
        // フォールバック
        window.location.href = '/add_book.php';
    }
}

// ジャンルカードクリック時の処理
function handleGenreCardClick(genreName) {
    // カテゴリ名はクリックしても何もしない（読了冊数別カテゴリ）
    if (genreName.includes('冊') && genreName.includes('著者')) {
        return;
    }
    // よく使うタグカテゴリの場合も何もしない
    if (genreName === 'よく使うタグ') {
        return;
    }
}

// タグ/著者検索処理
function searchByTag(searchType, searchWord, event) {
    if (event) {
        event.stopPropagation(); // イベント伝播を停止
    }
    
    
    // 本棚のタグ/著者検索ページに遷移
    const url = `/bookshelf.php?search_type=${searchType}&search_word=${encodeURIComponent(searchWord)}`;
    console.log('遷移先URL:', url);
    window.location.href = url;
}

// ジャンル詳細を表示
function displayGenreDetails(data) {
    const container = document.getElementById('genre-details');
    const genres = data.children || [];
    
    container.innerHTML = genres.map(genre => `
        <div class="genre-card bg-white rounded-lg shadow-sm p-4 border-l-4 hover:shadow-md transition-shadow cursor-pointer" 
             style="border-color: ${genre.color}"
             onclick="handleGenreCardClick('${genre.name}')">
            <h3 class="font-bold text-lg mb-3">${genre.name}</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">読了した本</span>
                    <span class="font-bold text-lg">${genre.value}冊</span>
                </div>
                ${genre.children && genre.children.length > 0 ? `
                <div class="mt-3 pt-3 border-t">
                    <p class="text-xs text-gray-500 mb-2">主な著者/タグ</p>
                    <div class="flex flex-wrap gap-1">
                        ${genre.children.slice(0, 3).map(child => {
                            const searchType = genre.name === 'よく使うタグ' ? 'tag' : 'author';
                            const cleanName = child.name.replace(/\s*\([^)]*\)$/, '');
                            return `
                            <span class="text-xs bg-gray-100 hover:bg-blue-100 px-2 py-1 rounded cursor-pointer transition-colors" 
                                  onclick="searchByTag('${searchType}', '${cleanName.replace(/'/g, "\\'")}', event)" 
                                  title="クリックして検索">
                                ${child.name}
                            </span>
                        `}).join('')}
                        ${genre.children.length > 3 ? `<span class="text-xs text-gray-400">他${genre.children.length - 3}件</span>` : ''}
                    </div>
                </div>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// ビュー切り替えを削除（バブル表示のみ）


// ズームボタンの機能
document.getElementById('zoom-in').addEventListener('click', function() {
    if (window.currentZoom && window.currentSvg) {
        window.currentSvg.transition()
            .duration(300)
            .call(window.currentZoom.scaleBy, 1.5);
    }
});

document.getElementById('zoom-out').addEventListener('click', function() {
    if (window.currentZoom && window.currentSvg) {
        window.currentSvg.transition()
            .duration(300)
            .call(window.currentZoom.scaleBy, 1 / 1.5);
    }
});

document.getElementById('reset-zoom').addEventListener('click', function() {
    if (window.currentZoom && window.currentSvg) {
        // 保存されたリセット変換を使用、なければデフォルト
        const transform = window.resetTransform || d3.zoomIdentity;
        window.currentSvg.transition()
            .duration(750)
            .call(window.currentZoom.transform, transform);
    }
});

// 画像を遅延読み込み
function loadImagesLazily() {
    
    // 著者画像を読み込み
    fetch(`/api/reading_map_images.php?user=${userId}&type=author`, {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            // 各著者ノードに画像を追加
            Object.keys(data.data).forEach(author => {
                const images = data.data[author];
                // DOMを更新（D3.jsを使用）
                const authorNodes = d3.selectAll('.node-author')
                    .filter(d => {
                        const authorName = d.data.author_name || d.data.name.replace(/ \(\d+冊\)$/, '');
                        return authorName === author;
                    });
                
                
                authorNodes.each(function(d) {
                    d.data.images = images;
                    // 画像を追加
                    const node = d3.select(this);
                    if (images.length > 0) {
                        addBookImages(node, d, images);
                    }
                });
            });
        }
    })
    .catch(error => {});
    
    // タグ画像を読み込み
    fetch(`/api/reading_map_images.php?user=${userId}&type=tag`, {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            // 各タグノードに画像を追加
            Object.keys(data.data).forEach(tag => {
                const images = data.data[tag];
                // DOMを更新（D3.jsを使用）
                const tagNodes = d3.selectAll('.node-tag')
                    .filter(d => {
                        const tagName = d.data.tag_name || d.data.name.replace(/ \(\d+冊\)$/, '');
                        return tagName === tag;
                    });
                
                
                tagNodes.each(function(d) {
                    d.data.images = images;
                    // 画像を追加
                    const node = d3.select(this);
                    if (images.length > 0) {
                        addBookImages(node, d, images);
                    }
                });
            });
        }
    })
    .catch(error => {});
}

// 単一の本の画像を追加
function addBookImage(node, image, x, y, size) {
    if (!image || !image.url) return;
    
    const clipId = `clip-${Math.random().toString(36).substr(2, 9)}`;
    
    // クリップパスを作成
    node.append('clipPath')
        .attr('id', clipId)
        .append('rect')
        .attr('x', x - size/2)
        .attr('y', y - size/2)
        .attr('width', size)
        .attr('height', size)
        .attr('rx', 4);
    
    // HTTPSに変換
    let imageUrl = image.url;
    if (imageUrl && imageUrl.includes('ecx.images-amazon.com')) {
        imageUrl = imageUrl.replace('http://', 'https://');
    }
    
    // 画像を追加
    node.append('image')
        .attr('href', imageUrl)
        .attr('x', x - size/2)
        .attr('y', y - size/2)
        .attr('width', size)
        .attr('height', size)
        .attr('clip-path', `url(#${clipId})`)
        .style('pointer-events', 'none')
        .on('error', function() {
            // 画像読み込みエラー時はデフォルト画像に差し替え
            d3.select(this).attr('href', '/img/no-image-book.png');
        });
}

// ノードに本の画像を追加（複数画像対応）
function addBookImages(node, d, images) {
    if (!images || images.length === 0) return;
    
    const nodeRadius = d.r;
    const numImages = Math.min(images.length, 3);
    
    // ノードサイズに応じて画像サイズを調整
    let imageSize;
    if (nodeRadius < 30) {
        imageSize = nodeRadius * 1.2; // 小さいノードは1枚の画像で覆う
    } else {
        imageSize = Math.min(nodeRadius * 0.6, 35); // 大きいノードは複数画像
    }
    
    if (numImages === 1 || nodeRadius < 30) {
        // 1枚の画像を中央に配置
        const clipId = `clip-${Math.random().toString(36).substr(2, 9)}`;
        
        node.append('clipPath')
            .attr('id', clipId)
            .append('circle')
            .attr('cx', 0)
            .attr('cy', 0)
            .attr('r', imageSize / 2);
        
        // Amazon画像URLをHTTPSに変換
        let imageUrl = images[0].url;
        if (imageUrl && imageUrl.includes('ecx.images-amazon.com')) {
            imageUrl = imageUrl.replace('http://', 'https://');
        }
        
        node.append('image')
            .attr('href', imageUrl)
            .attr('x', -imageSize / 2)
            .attr('y', -imageSize / 2)
            .attr('width', imageSize)
            .attr('height', imageSize)
            .attr('clip-path', `url(#${clipId})`)
            .style('pointer-events', 'none')
            .style('opacity', 0)
            .on('error', function() {
                // 画像読み込みエラー時はデフォルト画像に差し替え
                d3.select(this).attr('href', '/img/no-image-book.png');
            })
            .transition()
            .duration(300)
            .style('opacity', 0.85);
    } else {
        // 複数の画像を円形に配置
        const angleStep = (2 * Math.PI) / numImages;
        const distance = nodeRadius * 0.45;
        
        images.slice(0, numImages).forEach((img, i) => {
            const angle = -Math.PI / 2 + (i * angleStep); // 上から開始
            const x = Math.cos(angle) * distance;
            const y = Math.sin(angle) * distance;
            
            const clipId = `clip-${Math.random().toString(36).substr(2, 9)}`;
            
            node.append('clipPath')
                .attr('id', clipId)
                .append('circle')
                .attr('cx', x)
                .attr('cy', y)
                .attr('r', imageSize / 2);
            
            // Amazon画像URLをHTTPSに変換
            let imageUrl = img.url;
            if (imageUrl && imageUrl.includes('ecx.images-amazon.com')) {
                imageUrl = imageUrl.replace('http://', 'https://');
            }
            
            node.append('image')
                .attr('href', imageUrl)
                .attr('x', x - imageSize / 2)
                .attr('y', y - imageSize / 2)
                .attr('width', imageSize)
                .attr('height', imageSize)
                .attr('clip-path', `url(#${clipId})`)
                .style('pointer-events', 'none')
                .style('opacity', 0)
                .on('error', function() {
                    // 画像読み込みエラー時はデフォルト画像に差し替え
                    d3.select(this).attr('href', '/img/no-image-book.png');
                })
                .transition()
                .duration(300)
                .delay(i * 100)
                .style('opacity', 0.85);
        });
    }
}

// ズーム情報を更新する関数
function updateZoomInfo(scale) {
    const zoomInfo = document.getElementById('zoom-info');
    const zoomLevel = document.getElementById('zoom-level');
    
    if (zoomInfo && zoomLevel) {
        const percentage = Math.round(scale * 100);
        zoomLevel.textContent = percentage + '%';
        
        // ズーム情報を表示
        zoomInfo.classList.remove('hidden');
        
        // 3秒後に非表示
        clearTimeout(window.zoomInfoTimeout);
        window.zoomInfoTimeout = setTimeout(() => {
            zoomInfo.classList.add('hidden');
        }, 3000);
    }
}
</script>

<?php
// コンテンツを変数に格納
$d_content = ob_get_clean();

// ベーステンプレートを読み込む
include 'template/modern/t_base.php';
?>