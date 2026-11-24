import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Palette Or : de l'or clair (300) au bronze foncé (800-950)
const gold = {
    50: '#FBF6E8', 100: '#F6EBC9', 200: '#F3E3B0', 300: '#EBCB74', 400: '#D9B04A',
    500: '#C79A2E', 600: '#A67C1C', 700: '#8A6A1E', 800: '#4A3A14', 900: '#2E2410', 950: '#1C1710',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: { sans: ['Inter Variable', ...defaultTheme.fontFamily.sans] },
            colors: {
                gold,
                indigo: gold, // les anciennes classes indigo deviennent or
                gray: { 950: '#0A0A0A' }, // noir pur pour la barre latérale
            },
        },
    },
    plugins: [forms],
};
