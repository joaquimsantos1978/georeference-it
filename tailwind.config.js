import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                green: {
                    50:  '#f2f9ee',
                    100: '#e0f1d5',
                    200: '#c2e3ac',
                    300: '#9dd07c',
                    400: '#78bc52',
                    500: '#5dab38',
                    600: '#4C9C2E',
                    700: '#3d8024',
                    800: '#2f641b',
                    900: '#1f4311',
                    950: '#122609',
                },
            },
        },
    },

    plugins: [forms],
};
