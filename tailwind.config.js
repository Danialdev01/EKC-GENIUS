/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "**/*.php",
    "**/*.html",
  ],
  theme: {
    extend: {
      colors: {
        'edu': {
          '50': '#F0F9FF',
          '100': '#E0F2FE',
          '200': '#BAE6FD',
          '300': '#7DD3FC',
          '400': '#38BDF8',
          '500': '#0EA5E9',
          '600': '#0284C7',
          '700': '#0369A1',
          '800': '#075985',
          '900': '#0C4A6E',
        },
        'growth': {
          '50': '#ECFDF5',
          '100': '#D1FAE5',
          '200': '#A7F3D0',
          '300': '#6EE7B7',
          '400': '#34D399',
          '500': '#10B981',
          '600': '#059669',
          '700': '#047857',
          '800': '#065F46',
          '900': '#064E3B',
        },
        'trust': {
          '50': '#EEF2FF',
          '100': '#E0E7FF',
          '200': '#C7D2FE',
          '300': '#A5B4FC',
          '400': '#818CF8',
          '500': '#6366F1',
          '600': '#4F46E5',
          '700': '#4338CA',
          '800': '#3730A3',
          '900': '#312E81',
        },
        'slate': {
          '50': '#F8FAFC',
          '100': '#F1F5F9',
          '200': '#E2E8F0',
          '300': '#CBD5E1',
          '400': '#94A3B8',
          '500': '#64748B',
          '600': '#475569',
          '700': '#334155',
          '800': '#1E293B',
          '900': '#0F172A',
        },
        'paper-white': '#FAFBFC',
        'card-white': '#FFFFFF',
      },
      fontFamily: {
        'poppins': ['Poppins', 'Quicksand', 'Montserrat', 'sans-serif'],
        'inter': ['Inter', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
      },
      boxShadow: {
        'card': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05)',
        'card-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.05)',
        'login': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
        'button': '0 4px 14px 0 rgba(14, 165, 233, 0.25)',
      }
    },
  },
  plugins: [],
}