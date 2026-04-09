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
        },
    },

    safelist: [
        // Clases dinámicas de la planilla ponderada (notas/v2)
        'bg-blue-700', 'bg-purple-700', 'bg-green-700',
        'bg-blue-100', 'text-blue-800',
        'bg-purple-100', 'text-purple-800',
        'bg-green-100', 'text-green-800',
        'focus:ring-blue-400', 'focus:ring-purple-400', 'focus:ring-green-400',
    ],

    plugins: [forms],
};
