/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Livewire/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        'money-orange': '#FF6B35',
        'money-red': '#D62828',
        'money-dark': '#1A1A1A',
        'money-light': '#F8F9FA',
      }
    },
  },
  plugins: [],
}
