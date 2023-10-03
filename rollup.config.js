import svelte from 'rollup-plugin-svelte';
import resolve from '@rollup/plugin-node-resolve';
import commonjs from 'rollup-plugin-commonjs';
import css from 'rollup-plugin-css-only'; // Import the rollup-plugin-css-only

export default {
  input: 'resources/svelte/main.js', // Replace with the entry point for your application
  output: {
    file: 'public/assets/js/bundle.js', // Replace with the output path and filename
    format: 'iife', // Output format (immediately-invoked function expression)
    name: 'SvelteApp'
  },
  plugins: [
    svelte({
      // Svelte options here (e.g., preprocessors, CSS extraction)
    }),
    resolve({
      browser: true,
      dedupe: ['svelte'],
      exportConditions: ['node', 'module', 'svelte'],
    }),
    commonjs(),
    css({ output: 'public/assets/css/bundle.css' }), // Specify the output path for the CSS
  ],
};
