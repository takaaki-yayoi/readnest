// ReadNest Tailwind Configuration
// Based on the logo colors: Deep forest green with warm beige accent

module.exports = {
  theme: {
    extend: {
      colors: {
        // Primary colors from logo
        'readnest': {
          'green': '#1a4d3e', // Deep forest green from logo
          'green-light': '#2a6654',
          'green-dark': '#0f2f26',
          'beige': '#f5f1e8', // Warm beige background
          'beige-light': '#faf8f3',
          'beige-dark': '#e8e2d5',
        },
        // Semantic colors
        'primary': {
          50: '#e8f3f0',
          100: '#c4e1d8',
          200: '#9ccfbf',
          300: '#74bca6',
          400: '#56ae94',
          500: '#38a182',
          600: '#2a8066',
          700: '#1a4d3e', // Main brand color
          800: '#143a2f',
          900: '#0f2a22',
        },
        'accent': {
          50: '#fefdfb',
          100: '#faf8f3',
          200: '#f5f1e8', // Main beige
          300: '#e8e2d5',
          400: '#d6ccb9',
          500: '#c4b69d',
          600: '#9e8f76',
          700: '#7a6d58',
          800: '#5a4f40',
          900: '#3d342a',
        }
      },
      fontFamily: {
        'sans': ['Noto Sans JP', 'Helvetica Neue', 'Arial', 'sans-serif'],
        'serif': ['Noto Serif JP', 'Georgia', 'serif'],
      },
      boxShadow: {
        'soft': '0 2px 8px rgba(26, 77, 62, 0.08)',
        'medium': '0 4px 16px rgba(26, 77, 62, 0.12)',
        'large': '0 8px 32px rgba(26, 77, 62, 0.16)',
      }
    }
  }
}