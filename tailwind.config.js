/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./includes/**/*.php",
        "./accountant-dashboard/**/*.php",
        "./dashboard/**/*.php",
        "./sms-teacher/**/*.php",
        "./*.php"
    ],
    theme: {
        extend: {
            colors: {
                nskblue: '#1e40af',
                nsklightblue: '#3b82f6',
                nsknavy: '#1e3a8a',
                nskgold: '#f59e0b',
                nsklight: '#f0f9ff',
                nskgreen: '#10b981',
                nskred: '#ef4444'
            }
        }
    },
    plugins: [],
}
