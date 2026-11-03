import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// gold و indigo صارا بنفسجياً (اللون الأساسي)
const brand = {
    50: '#F5F3FF', 100: '#EDE9FE', 200: '#DDD6FE', 300: '#C4B5FD', 400: '#8B5CF6',
    500: '#7C3AED', 600: '#6D28D9', 700: '#5B21B6', 800: '#4C1D95', 900: '#3B0F7A', 950: '#2E1065',
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
                gold: brand,
                indigo: brand,
                gray: { 950: '#0B1020' }, // يُدمج مع الرمادي الافتراضي (الطبيعي الفاتح)
            },
        },
    },
    plugins: [forms],
};