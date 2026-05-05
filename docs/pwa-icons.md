# PWA アイコン生成ガイド

ReadNest を PWA 対応するために必要なアイコン PNG の仕様と配置パスをまとめる。
本ドキュメントは「何を、どこに、どうやって配置するか」を明確化するもの。実際の PNG 生成は別途デザイナー or 自動ツール（pwa-asset-generator 等）で行う。

## ブランド指針

| 項目 | 値 |
|------|-----|
| Primary color | `#1a4d3e` (Deep forest green) |
| Background color | `#f5f1e8` (Warm beige) |
| Accent color | `#38a182` |
| ソース素材 | `/apple-touch-icon.png` (600x600) または `/img/logo.png` |

> Maskable アイコンは「セーフエリア」を考慮し、ロゴを中央 80% 内に収めること。背景色は `#1a4d3e` 推奨。

## 必須 (manifest.json から参照)

| サイズ | パス | 用途 | purpose | 備考 |
|--------|------|------|---------|------|
| 192x192 | `/img/icons/icon-192.png` | Android ホーム画面 / マニフェスト最小要件 | `any` | 透過は不要、背景はブランドカラーで塗る |
| 512x512 | `/img/icons/icon-512.png` | スプラッシュ・大型表示 | `any` | 同上 |
| 512x512 | `/img/icons/icon-512-maskable.png` | Android アダプティブアイコン | `maskable` | ロゴは中央80%以内、外周はブランド背景色で埋める |

## iOS 必須 (apple-touch-icon)

| サイズ | パス | 用途 |
|--------|------|------|
| 180x180 | `/img/icons/apple-touch-icon-180.png` | iOS「ホーム画面に追加」アイコン |

> ⚠️ 過去に `apple-touch-icon` は「Safari のサイトプレビューで大きく表示される」を理由に意図的に未設定だった。今回 PWA 対応のため復活させる（[t_base.php](../template/modern/t_base.php) のコメント参照）。

## 推奨（任意）— iOS スプラッシュ画面

iOS は `<link rel="apple-touch-startup-image">` で端末ごとに別解像度を要求する。設定すれば standalone 起動時の白い空白を回避できる（ベターUX）。

| 端末 | サイズ (横x縦) | パス |
|------|---------------|------|
| iPhone 14 Pro Max / 15 Pro Max | 1290x2796 | `/img/icons/splash-1290x2796.png` |
| iPhone 14 Pro / 15 Pro | 1179x2556 | `/img/icons/splash-1179x2556.png` |
| iPhone 13/14/15 (標準) | 1170x2532 | `/img/icons/splash-1170x2532.png` |
| iPhone 13 mini / 12 mini | 1080x2340 | `/img/icons/splash-1080x2340.png` |
| iPhone 11 / XR | 828x1792 | `/img/icons/splash-828x1792.png` |
| iPhone 8 Plus / 7 Plus | 1242x2208 | `/img/icons/splash-1242x2208.png` |
| iPhone 8 / 7 / SE2/3 | 750x1334 | `/img/icons/splash-750x1334.png` |
| iPad Pro 12.9" | 2048x2732 | `/img/icons/splash-2048x2732.png` |
| iPad Pro 11" / Air | 1668x2388 | `/img/icons/splash-1668x2388.png` |
| iPad 10.2" | 1620x2160 | `/img/icons/splash-1620x2160.png` |
| iPad mini | 1488x2266 | `/img/icons/splash-1488x2266.png` |

スプラッシュ画像を配置した場合、`template/modern/t_base.php` の `<head>` に以下を追加：

```html
<link rel="apple-touch-startup-image"
      media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3)"
      href="/img/icons/splash-1290x2796.png">
<!-- 他端末分も同様に media クエリで出し分け -->
```

## ディレクトリ作成

新規ディレクトリ `/img/icons/` をリポジトリ直下に作成し、上記 PNG を配置する。

```bash
mkdir -p /path/to/readnest/icons
```

## 生成方法の例

### 1. オンラインツール
- [Maskable.app Editor](https://maskable.app/editor) — maskable アイコンのセーフエリア確認
- [PWA Builder Image Generator](https://www.pwabuilder.com/imageGenerator) — 一括生成

### 2. CLI (推奨)
```bash
npx pwa-asset-generator ./img/logo-source.png ./icons \
  --background "#1a4d3e" \
  --padding "10%" \
  --icon-only \
  --favicon
```

### 3. ImageMagick (最低限)
```bash
# 元画像 (apple-touch-icon.png 600x600) からリサイズ
convert apple-touch-icon.png -resize 192x192 icons/icon-192.png
convert apple-touch-icon.png -resize 512x512 icons/icon-512.png
convert apple-touch-icon.png -resize 180x180 icons/apple-touch-icon-180.png

# Maskable: 背景色で余白埋め
convert apple-touch-icon.png -resize 410x410 \
  -gravity center -background "#1a4d3e" -extent 512x512 \
  icons/icon-512-maskable.png
```

## 配置後のチェック

- [ ] `/img/icons/icon-192.png` と `/img/icons/icon-512.png` がブラウザで直接開ける
- [ ] Chrome DevTools → Application → Manifest にアイコンが表示される
- [ ] iOS Safari で「ホーム画面に追加」したアイコンがブランドカラーで表示される
- [ ] Android Chrome で「アプリをインストール」プロンプトが出る
- [ ] Maskable アイコンが [Maskable.app](https://maskable.app/) でセーフエリア内に収まっている
