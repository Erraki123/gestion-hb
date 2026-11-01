import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// ذهبي مناسب للخلفيات الداكنة (الدرجات الفاتحة صارت داكنة والعكس)
const gold = {
    50: '#1E1707', 100: '#2A2008', 200: '#5C460F', 300: '#EBD797', 400: '#DFBF63',
    500: '#C9A227', 600: '#C9A227', 700: '#D2AA3A', 800: '#DFBF63', 900: '#EBD797', 950: '#FFF3C4',
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
                indigo: gold,
                // رمادي معكوس: الفاتح صار داكناً والداكن صار فاتحاً
                gray: {
                    50: '#1A1915', 100: '#0B0B0A', 200: '#2A2822', 300: '#3A372F', 400: '#78746A',
                    500: '#A7A397', 600: '#D3CFC2', 700: '#E7E4DA', 800: '#F4F2EC', 900: '#FAF9F6', 950: '#050504',
                },
            },
        },
    },
    plugins: [forms],
};
