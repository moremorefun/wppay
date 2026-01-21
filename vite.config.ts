import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

// WordPress external dependencies
const wpExternals = {
  '@wordpress/element': 'wp.element',
  '@wordpress/components': 'wp.components',
  '@wordpress/blocks': 'wp.blocks',
  '@wordpress/block-editor': 'wp.blockEditor',
  '@wordpress/data': 'wp.data',
  '@wordpress/i18n': 'wp.i18n',
  '@wordpress/api-fetch': 'wp.apiFetch',
  'react': 'React',
  'react-dom': 'ReactDOM',
};

export default defineConfig(({ mode }) => ({
  plugins: [react()],

  build: {
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: mode === 'development',
    rollupOptions: {
      input: {
        admin: resolve(__dirname, 'src/admin/index.tsx'),
        block: resolve(__dirname, 'src/block/index.tsx'),
        shortcode: resolve(__dirname, 'src/shortcode/index.tsx'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) {
            return '[name].css';
          }
          return 'assets/[name]-[hash][extname]';
        },
      },
      external: Object.keys(wpExternals),
    },
  },

  define: {
    'process.env.NODE_ENV': JSON.stringify(mode),
  },

  server: {
    port: 3000,
    cors: true,
    origin: 'http://localhost:3000',
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
      '@shared': resolve(__dirname, 'src/shared'),
    },
  },
}));
