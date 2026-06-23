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
                sans: ['"Bai Jamjuree"', '"Rubik"', ...defaultTheme.fontFamily.sans],
                display: ['"Bai Jamjuree"', '"Rubik"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
                arabic: ['"IBM Plex Sans Arabic"', '"Rubik"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Bite brand primitives — fixed hex (not per-shop). Pair lime with forest text.
                forest: '#004225',
                pine: '#0B6B2E',
                lime: '#7AC70C',
                olive: '#B7C40D',
                mist: '#ECF1E6',
                cream: '#F6F8F1',
                paper: 'rgb(var(--paper) / <alpha-value>)',
                ink: 'rgb(var(--ink) / <alpha-value>)',
                crema: 'rgb(var(--crema) / <alpha-value>)',
                canvas: 'rgb(var(--canvas) / <alpha-value>)',
                panel: 'rgb(var(--panel) / <alpha-value>)',
                muted: 'rgb(var(--panel-muted) / <alpha-value>)',
                line: 'rgb(var(--line) / <alpha-value>)',
                'ink-soft': 'rgb(var(--ink-soft) / <alpha-value>)',
                signal: 'rgb(var(--signal) / <alpha-value>)',
                alert: 'rgb(var(--alert) / <alpha-value>)',
                matcha: 'rgb(var(--signal) / <alpha-value>)',
                berry: 'rgb(var(--alert) / <alpha-value>)',
                vellum: 'rgb(var(--panel) / <alpha-value>)',
                graphite: 'rgb(var(--line) / <alpha-value>)',
                border: 'rgb(var(--line) / <alpha-value>)',
            },
        },
    },

    plugins: [forms],
};
