import skipFormatting from '@vue/eslint-config-prettier/skip-formatting';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import pluginVue from 'eslint-plugin-vue';

export default defineConfigWithVueTs(
  {
    name: 'rimef/files',
    files: ['**/*.{ts,vue}'],
  },
  {
    name: 'rimef/ignores',
    ignores: ['dist/**', 'node_modules/**'],
  },
  pluginVue.configs['flat/recommended'],
  vueTsConfigs.recommended,
  {
    name: 'rimef/rules',
    rules: {
      'no-console': 'warn',
      'prefer-const': 'error',
    },
  },
  skipFormatting
);
