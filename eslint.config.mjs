import globals from 'globals';
import neostandard from 'neostandard';

export default [
    ...neostandard(),
    {
        languageOptions: {
            ecmaVersion: 2018,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
                ...globals.jasmine,
                Atomics: 'readonly',
                SharedArrayBuffer: 'readonly',
                jasmine: true
            }
        },

        rules: {
            '@stylistic/linebreak-style': ['error', 'unix'],
        }
    }
];
