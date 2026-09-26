import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Design tokens for the storefront refresh. The default Tailwind palette is untouched,
                // so pages that haven't migrated yet keep their existing colors.
                brand: {
                    DEFAULT: '#0B6E6B',
                    dark: '#085552',
                    tint: '#E3F1F0',
                    soft: '#9FD8D3',
                },
                ink: {
                    DEFAULT: '#12262B',
                    soft: '#4A5E63',
                    faint: '#6F8287',
                },
                seal: '#104888',
                mist: '#EEF3F3',
                line: '#D3DCDC',
                danger: '#B3261E',
            },
            borderRadius: {
                control: '4px',
                card: '8px',
            },
        },
    },
    plugins: [],
};
