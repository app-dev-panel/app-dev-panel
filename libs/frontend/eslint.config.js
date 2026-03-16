import tsParser from '@typescript-eslint/parser';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import prettierConfig from 'eslint-config-prettier';

export default [
    {
        files: ['packages/*/src/**/*.{ts,tsx}'],
        languageOptions: {
            parser: tsParser,
            parserOptions: {ecmaVersion: 2022, sourceType: 'module'},
        },
        plugins: {'@typescript-eslint': tsPlugin},
        rules: {
            ...tsPlugin.configs.recommended.rules,
            '@typescript-eslint/no-unused-vars': 'warn',
            '@typescript-eslint/ban-ts-comment': 'warn',
            '@typescript-eslint/consistent-type-definitions': ['error', 'type'],
            '@typescript-eslint/no-explicit-any': 'off',
        },
    },
    prettierConfig,
];
