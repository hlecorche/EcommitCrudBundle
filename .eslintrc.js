module.exports = {
    env: {
        'browser': true,
        'es6': true,
        'jasmine': true,
        'node': true
    },
    extends: [
        'standard'
    ],
    globals: {
        Atomics: 'readonly',
        SharedArrayBuffer: 'readonly'
    },
    ignorePatterns: ['/src/Resources/public/js/scrollToFirstMessage.js'],
    parserOptions: {
        ecmaVersion: 2018,
        sourceType: 'module'
    },
    rules: {
        'indent': ['error', 4],
        'linebreak-style': ['error', 'unix'],
        'no-eval': 'off',
        'semi': 'off'
    }
}
