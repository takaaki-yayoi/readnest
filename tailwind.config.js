/** @type {import('tailwindcss').Config} */
// このファイルは本番用の静的CSS (/css/tailwind.css) をビルドするための設定。
// 内容は従来 t_base.php に inline で書いていた tailwind.config と一致させてある
// （darkMode / screens / colors）。クラスの追加・変更時は `npm run build:css` を実行する。
module.exports = {
  darkMode: 'class', // クラスベースのダークモード（dark: バリアントで使用）
  content: [
    "./template/**/*.php",
    "./i/template/**/*.php",
    "./iphone/template/**/*.php",
    "./library/**/*.php",
    "./*.php",
    "./js/**/*.js"
  ],
  // 文字列連結で動的生成されるクラス（スキャナが検出できない分）を明示的に残す。
  // 例: t_debug_streak.php の text-{color}-500
  safelist: [
    'text-green-500',
    'text-orange-500',
    'text-red-500',
    'text-yellow-500',
    'text-purple-500',
  ],
  theme: {
    extend: {
      screens: {
        'xs': '475px',
        // Tailwindのデフォルト: sm: 640px, md: 768px, lg: 1024px, xl: 1280px, 2xl: 1536px
        'tablet': '768px',       // タブレット縦向き
        'tablet-lg': '1024px',   // タブレット横向き
        'landscape': { 'raw': '(orientation: landscape) and (max-height: 500px)' }, // スマホ横向き
        'tall': { 'raw': '(min-height: 800px)' }, // 縦長画面
      },
      colors: {
        // サイト基調色（t_base.php inline config と同一）
        'readnest-primary': '#1a4d3e',
        'readnest-beige': '#f5f1e8',
        'readnest-accent': '#38a182',
        // 読書アプリ用のカスタムカラーパレット
        'book-primary': {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
        },
        'book-secondary': {
          50: '#fef3c7',
          100: '#fee8a0',
          200: '#fdd458',
          300: '#fcbf24',
          400: '#f59e0b',
          500: '#d97706',
          600: '#b45309',
          700: '#92400e',
          800: '#713f12',
          900: '#5a3517',
        },
        'book-accent': '#901808',
      },
      fontFamily: {
        'sans': ['Noto Sans JP', 'メイリオ', 'Meiryo', 'sans-serif'],
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
      },
    },
  },
  plugins: [],
}
