const colors = require('tailwindcss/colors')
const defaultTheme = require('tailwindcss/defaultTheme')

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
      './resources/**/*.blade.php',
      './vendor/filament/**/*.blade.php',
      './vendor/andrewdwallo/**/*.blade.php'
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        danger: colors.rose,
        primary: {
          50: '#F2F3FA',
          100: '#D8DBF5',
          200: '#B4B9EB',
          300: '#9297E1',
          400: '#7075D7',
          500: '#454DC8',
          600: '#27285C',
          700: '#2D2F6A',
          800: '#37387E',
          900: '#414292',
        },
        success: colors.green,
        warning: colors.yellow,
        platinum: '#E8E9EB',
        moonlight: '#F6F5F3',
        translucent: 'rgba(54, 54, 52, 0.06)',
      },
      fontFamily: {
        sans: ['DM Sans', ...defaultTheme.fontFamily.sans],
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}

