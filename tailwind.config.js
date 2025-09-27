/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./template/**/*.php",
    "./i/template/**/*.php",
    "./iphone/template/**/*.php",
    "./*.php",
    "./js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
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