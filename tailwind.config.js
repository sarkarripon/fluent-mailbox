/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/**/*.{vue,js,ts,jsx,tsx}',
        './fluent-mailbox.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#2563EB', // Example primary color
            },
        },
    },
    plugins: [],
}
