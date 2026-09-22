/** @type {import('tailwindcss').Config} */

export default {
  mode: 'jit',
  content: ['./templates/**/*.twig', './src/**/*.css'],
  theme: {
    "borderRadius": {
      'none': '0',
      'sm': '0.125rem',
      DEFAULT: '0.25rem',
      'md': '0.375rem',
      'lg': '0.5rem',
      'full': '9999px',
      'large': '12px',
    },
    extend: {
      fontFamily: {
        'roboto': ['Roboto', 'sans-serif'],
        'noto': ['Noto Serif', 'serif'],
        'text': ['Halant', 'serif'],
        'title': ['Abril Fatface', 'serif'],
      },
      "colors": {
        // custom color pallet generated here: https://www.tints.dev/brand/4DD3FF
        // add as many named colors as you feel you need
        "primary": {
          default: "#70DBFF",
          50: "#F0FBFF",
          100: "#DBF6FF",
          200: "#B8EDFF",
          300: "#94E4FF",
          400: "#70DBFF",
          500: "#4DD3FF",
          600: "#0AC2FF",
          700: "#0095C7",
          800: "#006385",
          900: "#003242",
          950: "#001B24"
        },
        "surface": {
          default: "#5D6F79",
          50: "#F1F3F4",
          100: "#E5E9EB",
          200: "#C8D0D5",
          300: "#AEBAC1",
          400: "#92A2AB",
          500: "#788C97",
          600: "#5D6F79",
          700: "#47555C",
          800: "#2F383D",
          900: "#181D20",
          950: "#0B0D0E"
        }
      },
      // customizing rich text involves extending the typography plugin - here is an example of how it can be done.
      // documentation here: https://github.com/tailwindlabs/tailwindcss-typography?tab=readme-ov-file#adding-custom-color-themes
      typography: ({ theme }) => ({
        textDark: {
          css: {
            '--tw-prose-body': theme('colors.stone[800]'),
            '--tw-prose-headings': theme('colors.stone[900]'),
            '--tw-prose-lead': theme('colors.stone[700]'),
            '--tw-prose-links': theme('colors.stone[900]'),
            '--tw-prose-bold': theme('colors.stone[900]'),
            '--tw-prose-counters': theme('colors.stone[600]'),
            '--tw-prose-bullets': theme('colors.stone[400]'),
            '--tw-prose-hr': theme('colors.stone[300]'),
            '--tw-prose-quotes': theme('colors.stone[900]'),
            '--tw-prose-quote-borders': theme('colors.stone[300]'),
            '--tw-prose-captions': theme('colors.stone[700]'),
            '--tw-prose-code': theme('colors.stone[900]'),
            '--tw-prose-pre-code': theme('colors.stone[100]'),
            '--tw-prose-pre-bg': theme('colors.stone[900]'),
            '--tw-prose-th-borders': theme('colors.stone[300]'),
            '--tw-prose-td-borders': theme('colors.stone[200]'),
            '--tw-prose-invert-body': theme('colors.stone[200]'),
            '--tw-prose-invert-headings': theme('colors.white'),
            '--tw-prose-invert-lead': theme('colors.stone[300]'),
            '--tw-prose-invert-links': theme('colors.white'),
            '--tw-prose-invert-bold': theme('colors.white'),
            '--tw-prose-invert-counters': theme('colors.stone[400]'),
            '--tw-prose-invert-bullets': theme('colors.stone[600]'),
            '--tw-prose-invert-hr': theme('colors.stone[700]'),
            '--tw-prose-invert-quotes': theme('colors.stone[100]'),
            '--tw-prose-invert-quote-borders': theme('colors.stone[700]'),
            '--tw-prose-invert-captions': theme('colors.stone[400]'),
            '--tw-prose-invert-code': theme('colors.white'),
            '--tw-prose-invert-pre-code': theme('colors.stone[300]'),
            '--tw-prose-invert-pre-bg': 'rgb(0 0 0 / 50%)',
            '--tw-prose-invert-th-borders': theme('colors.stone[600]'),
            '--tw-prose-invert-td-borders': theme('colors.stone[700]'),
          },
        },
      }),
    },
  },
  plugins: [
    require("@tailwindcss/typography"),
    require('tailwindcss-animated'),
    require('tailwindcss-intersect'),
    require('@tailwindcss/forms'),
  ],
}