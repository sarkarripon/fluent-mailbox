/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/**/*.{vue,js,ts,jsx,tsx}',
        './fluent-mailbox.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: 'var(--fm-primary)',
                    hover: 'var(--fm-primary-hover)',
                    light: 'var(--fm-primary-light)',
                },
                surface: {
                    page: 'var(--fm-bg-page)',
                    sidebar: 'var(--fm-bg-sidebar)',
                    card: 'var(--fm-bg-card)',
                    hover: 'var(--fm-bg-hover)',
                },
            },
            textColor: {
                'fm-primary': 'var(--fm-text-primary)',
                'fm-secondary': 'var(--fm-text-secondary)',
                'fm-muted': 'var(--fm-text-muted)',
            },
            borderColor: {
                'fm-default': 'var(--fm-border)',
                'fm-light': 'var(--fm-border-light)',
            },
            boxShadow: {
                'fm-sm': 'var(--fm-shadow-sm)',
                'fm': 'var(--fm-shadow)',
                'fm-lg': 'var(--fm-shadow-lg)',
            },
        },
    },
    plugins: [],
}
