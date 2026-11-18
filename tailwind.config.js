const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Http/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: {
                    DEFAULT: '#0d0b1a',
                    900: '#0d0b1a',
                    800: '#15122a',
                    700: '#1d1936',
                    600: '#272245',
                },
                // Violet-tinted greys so any leftover gray-* utilities sit inside the theme.
                gray: {
                    50: '#f6f5fb',
                    100: '#ecebf5',
                    200: '#d6d3e8',
                    300: '#b4afd0',
                    400: '#8a84ad',
                    500: '#676189',
                    600: '#4b4669',
                    700: '#2f2b48',
                    800: '#1c1934',
                    900: '#13112a',
                    950: '#0d0b1a',
                },
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
    safelist: [
        'text-green-400',
        'text-green-600',
        'text-yellow-500',
        'text-red-500',
        'bg-emerald-400', 'text-emerald-950',
        'bg-lime-400', 'text-lime-950',
        'bg-yellow-400', 'text-yellow-950',
        'bg-rose-500', 'text-white',
    ],
};
