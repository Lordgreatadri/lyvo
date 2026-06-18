const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'Inter', ...defaultTheme.fontFamily.sans],
                display: ['Sora', 'Figtree', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                // LYVO brand system — Legitimate Yielding Verified Operators
                brand: {
                    blue: '#0B5FA5', // Deep Blue
                    teal: '#0F9B8E', // Teal
                    green: '#0EA86F', // Trust Green
                    'green-deep': '#0B8F6A', // Deep Green
                },
                // Primary scale anchored on Trust Green
                primary: {
                    50: '#ecfdf5',
                    100: '#d1fae5',
                    200: '#a7f3d0',
                    300: '#6ee7b7',
                    400: '#34d399',
                    500: '#0EA86F',
                    600: '#0B8F6A',
                    700: '#0a7557',
                    800: '#0a5d46',
                    900: '#094c3b',
                    950: '#022c22',
                },
                ink: {
                    DEFAULT: '#0B1220', // Dark Navy
                    soft: '#1c2333',
                    muted: '#64748b',
                },
                surface: {
                    DEFAULT: '#FFFFFF',
                    muted: '#F5F7FA', // Light Gray
                },
            },

            borderRadius: {
                xl: '16px',
                '2xl': '20px',
                '3xl': '28px',
            },

            boxShadow: {
                soft: '0 4px 24px -8px rgba(11, 18, 32, 0.12)',
                card: '0 8px 30px -12px rgba(11, 18, 32, 0.18)',
                glow: '0 10px 40px -10px rgba(14, 168, 111, 0.45)',
            },

            backgroundImage: {
                'lyvo-gradient': 'linear-gradient(120deg, #0B5FA5 0%, #0F9B8E 45%, #0EA86F 100%)',
                'lyvo-gradient-soft': 'linear-gradient(120deg, rgba(11,95,165,0.08) 0%, rgba(15,155,142,0.08) 50%, rgba(14,168,111,0.08) 100%)',
                'lyvo-radial': 'radial-gradient(1200px 600px at 80% -10%, rgba(15,155,142,0.25), transparent 60%), radial-gradient(900px 500px at -10% 10%, rgba(11,95,165,0.22), transparent 55%)',
            },

            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-12px)' },
                },
                'pulse-ring': {
                    '0%': { transform: 'scale(0.85)', opacity: '0.7' },
                    '100%': { transform: 'scale(1.6)', opacity: '0' },
                },
            },

            animation: {
                float: 'float 6s ease-in-out infinite',
                'pulse-ring': 'pulse-ring 2.4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
