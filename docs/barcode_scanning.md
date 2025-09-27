# バーコードスキャン機能

ReadNestにバーコードスキャン機能を追加しました。この機能により、本の裏面にあるISBNバーコードをスマートフォンやPCのカメラで読み取って、素早く本を検索・追加できるようになります。

## 機能概要

### 主要機能
- **ISBNバーコード読み取り**: ISBN-10、ISBN-13形式のバーコードに対応
- **自動検索**: バーコード読み取り後、自動的に本の検索を実行
- **カメラ対応**: スマートフォンの背面カメラ、PCのウェブカメラに対応
- **エラーハンドリング**: カメラアクセスエラーや読み取りエラーの適切な処理

### 対応バーコード
- **ISBN-10**: 10桁の国際標準図書番号
- **ISBN-13**: 13桁の国際標準図書番号（978または979で始まる）
- **EAN-13**: 13桁の European Article Number（ISBNを含む）

## 実装ファイル

### JavaScript ライブラリ
- **`js/barcode-scanner.js`**: バーコードスキャンのメインライブラリ
  - `BarcodeScanner` クラス: QuaggaJS ベースの実装
  - `ZXingBarcodeScanner` クラス: ZXing ベースのフォールバック実装

### PHP ファイル
- **`add_book.php`**: ISBNパラメータでの検索対応を追加
- **`library/book_search.php`**: ISBN検索の最適化
  - `isISBN()` 関数: ISBN形式の判定
  - `buildSearchQuery()` 関数: ISBN専用検索クエリの構築

### テンプレート
- **`template/modern/t_add_book.php`**: バーコードスキャンUIの実装
  - バーコードボタン
  - スキャナーモーダル
  - カメラプレビュー
  - 結果処理

## 使用方法

### 基本的な使い方
1. 本追加ページ（`add_book.php`）にアクセス
2. 「バーコード」ボタンをクリック
3. カメラアクセスを許可
4. 「スキャン開始」ボタンを押す
5. 本の裏面のISBNバーコードをカメラに向ける
6. 読み取り成功後、自動的に検索が実行される

### モバイルでの使用
- スマートフォンでは背面カメラが優先的に選択される
- 縦向き・横向き両方に対応
- タッチ操作でのフォーカス調整が可能

## 技術仕様

### 使用ライブラリ
```javascript
// メインライブラリ（QuaggaJS）
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>

// フォールバックライブラリ（ZXing）
<script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
```

### カメラ設定
```javascript
constraints: {
    width: { min: 640 },
    height: { min: 480 },
    facingMode: "environment" // 背面カメラを優先
}
```

### 読み取り設定
```javascript
decoder: {
    readers: [
        "ean_reader",      // EAN-13 (ISBN-13)
        "ean_8_reader",    // EAN-8
        "code_128_reader", // Code 128
        "code_39_reader"   // Code 39
    ]
}
```

## 検索の最適化

### ISBN検索の改善
```php
// ISBN専用検索クエリ
if (isISBN($keyword)) {
    $clean_isbn = str_replace(['-', ' '], '', $keyword);
    return "isbn:{$clean_isbn}";
}
```

### Google Books API統合
- ISBN検索時は `isbn:` プレフィックスを使用
- より正確な検索結果を取得
- 高解像度画像の優先取得

## エラーハンドリング

### カメラアクセスエラー
```javascript
if (error.name === 'NotAllowedError') {
    message = 'カメラへのアクセスが許可されていません。';
} else if (error.name === 'NotFoundError') {
    message = 'カメラが見つかりません。';
} else if (error.name === 'NotReadableError') {
    message = 'カメラが使用できません。';
}
```

### 読み取りエラー
- 無効なバーコード形式の検出
- 読み取り失敗時の再試行
- ネットワークエラーの処理

## セキュリティ考慮事項

### カメラアクセス
- HTTPSが必要（カメラアクセスのブラウザ要件）
- ユーザーの明示的な許可が必要
- カメラストリームの適切な解放

### データ処理
- 読み取ったISBNの検証
- SQLインジェクション対策
- XSS対策

## パフォーマンス

### 最適化項目
- カメラストリームの効率的な処理
- バーコード読み取りの頻度制御（1秒間のクールダウン）
- 不要なメモリ使用の回避
- 適切なリソース解放

### 推奨環境
- **モバイル**: iOS 11+, Android 7+
- **デスクトップ**: Chrome 60+, Firefox 55+, Safari 11+
- **カメラ**: 解像度 640x480 以上
- **ネットワーク**: 安定したインターネット接続

## トラブルシューティング

### よくある問題

#### カメラが起動しない
- ブラウザの設定でカメラアクセスを許可
- HTTPSでアクセスしているか確認
- 他のアプリケーションでカメラを使用していないか確認

#### バーコードが読み取れない
- 照明を改善する
- カメラとバーコードの距離を調整
- バーコードの汚れやキズを確認

#### 検索結果が表示されない
- インターネット接続を確認
- APIサーバーの状態を確認
- ISBNの有効性を確認

### デバッグ方法
```javascript
// デバッグモード有効化
console.log('Scanner initialized:', currentScanner);
console.log('Scan result:', result);
```

## テスト

### テストページ
`test_barcode.html` を使用して基本的な動作確認が可能

### テスト手順
1. テストページにアクセス
2. カメラアクセスを許可
3. サンプルバーコードで読み取りテスト
4. 結果の表示確認

## 今後の拡張予定

### 機能追加
- バーコード読み取り履歴の保存
- 連続スキャン機能
- オフライン対応
- 複数フォーマットへの対応拡張

### UI改善
- スキャン範囲の視覚的表示
- 読み取り音の設定
- 暗い環境での読み取り改善

## 参考資料

- [QuaggaJS Documentation](https://serratus.github.io/quaggaJS/)
- [ZXing-js Documentation](https://github.com/zxing-js/library)
- [Google Books API](https://developers.google.com/books/docs/v1/using)
- [MediaDevices.getUserMedia() API](https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia)